<?php

namespace robyj\csv2meta\Commands;

/**
 * @csv2meta.module
 *
 * Module to convert a CSV file to metadata using YAML to control
 * how that would happen
 */

use robyj\csv2meta\Objects\MetadataElement;
use robyj\csv2meta\Objects\MetadataManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

const FILE_PREFIX = "item";
const DEFAULT_EXT = "txt";

class Conversion extends Command
{

    /**
     * The parsed Yaml config file.
     *
     * @var array
     */
    private $yaml;

    /**
     * Metadata elements.
     *
     * @var array
     */
    private $metadataMgr;

    /**
     * The twig environment to render with.
     *
     * @var \Twig\Environment
     */
    private $twig;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('csv2meta')
            ->setDescription('Convert CSV to metadata.')
            ->addArgument('csvfile', InputArgument::REQUIRED,
              'Path to the csv file to use as metadata source')
            ->addArgument('yamlfile', InputArgument::REQUIRED,
              'Path to the YAML configuration file.')
            ->addArgument('directory', InputArgument::REQUIRED,
              'Path to output directory')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL,
                'The output metadata format', 'MODS')
            ->addOption('zipfile', 'z', InputArgument::OPTIONAL,
              'File name to output zipfile')
            ->addOption('delimiter', 'd', InputArgument::OPTIONAL,
              'Delimiter', "\t")
            ->addOption('extension', 'x', InputOption::VALUE_OPTIONAL,
                'The extension to use for output files.', 'txt');
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (!is_null($input->getArgument('yamlfile'))) {
            $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../templates');
            $this->twig = new \Twig\Environment($loader);
            $yamlname = $input->getArgument('yamlfile');
            // read in the YAML glue file
            $yaml = Yaml::parseFile($yamlname);
            if ($yaml === false) {
                $output->setDecorated(true);
                $output->writeln("ERROR parsing Yaml file ({$yamlname})");
                $output->setDecorated(false);
                exit(1);
            }
            $this->yaml = $yaml;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $header = "";
        $this->metadataMgr = new MetadataManager();

        $delim = $input->getOption('delimiter');
        $extension = $input->getOption('extension');

        // set this in case the CSV was formatted on a mac
        ini_set('auto_detect_line_settings', true);

        // start reading the CSV file
        $csv = fopen($input->getArgument('csvfile'), "r");

        if ($csv !== false) {
            $zip = null;
            if ($input->hasOption('zipname')) {
                $zip = new \ZipArchive();
                $zip->open($input->getOption('zipname'), \ZipArchive::CREATE);
            }
            // for each row of the csv, read it. ignore whitespace, first
            // populated line is the header for the YAML conversion values
            // everything else is one item per row
            $headerfound = false;
            while ((!feof($csv)) && ($headerfound == false)) {
                if (!$line = fgetcsv($csv, 1024, $delim)) {
                    continue;
                }
                // at this point, $line contains the first line in the file
                // which is supposed to be the header that is used for the
                // YAML Header => MODS/PBCORE/?? conversion
                $header = $line;
                $headerfound = true;
            }

            # Initialize all the fields in the records.
            foreach ($header as $headval) {
                if (isset($this->yaml[$headval]) && is_array($this->yaml[$headval])) {
                    $this->metadataMgr->addField($headval, $this->parseYamlStruct($headval, $this->yaml[$headval]));
                }
            }

            # Now do all the leftover fields
            $leftover = array_diff_key($this->yaml, $this->metadataMgr->getAllFields());
            # Parse all fields in the Yaml
            foreach ($leftover as $item => $data) {
                if (is_array($data)) {
                    $this->metadataMgr->addField($item, $this->parseYamlStruct($item, $data));
                }
            }

            // continue with the rest of the lines in the file
            $itemcnt = 1;

            while (($line = fgetcsv($csv, 1024, $delim)) !== false) {
                $filename = "";
                // initialize the DOM/XML object

                // for each cell, work out the correct MD field
                for ($cnt = 0; $cnt < sizeof($line); $cnt++) {
                    $headval = $header[$cnt];
                    if (isset($this->yaml['filename']) && $this->yaml['filename'] == $headval) {
                        # This is the filename column
                        $filename = $line[$cnt];
                    }

                    $mdval = $line[$cnt];
                    if ($this->metadataMgr->hasField($headval)) {
                        $this->metadataMgr->getField($headval)->setValue($mdval);
                    }
                }

                $dataArray = $this->metadataMgr->getMetadataArray();
                $template = $this->twig->load('mods.twig');
                $metadata = $template->render($dataArray);


                if (strlen($filename) == 0) {
                    $filename = sprintf("%s%04d.%s", FILE_PREFIX, $itemcnt,
                      $extension);
                } else {
                    $filename = sprintf("%s.%s", $filename, $extension);
                }

                $outpath = sprintf("%s/%s", $input->getArgument('directory'), $filename);
                $outfile = fopen($outpath, "w");
                if ($outfile != false) {
                    fputs($outfile, $metadata);
                    fclose($outfile);
                    /* if the user selected a zip file output, add the
                       metadata files to a zip file
                    */
                    if (!is_null($zip)) {
                        $zip->addFile($outpath, basename($outpath));
                    }
                }
                $itemcnt = $itemcnt + 1;
            }
            if (!is_null($zip)) {
                $zip->close();
            }
        }
        fclose($csv);
    }


    /**
     * @param string $name
     *  Yaml config field name.
     * @param array $yaml
     *  Yaml configuration data for the above field.
     * @return \robyj\csv2meta\Objects\MetadataElement
     *  The metadata configured object.
     * @throws \Exception
     *  When you try to construct using a string instead of an array.
     */
    private function parseYamlStruct($name, array $yaml)
    {
        $specialFields = [
            # the twig template variable this maps to.
            'twigVar',
            # attributes tag.
            'attributes',
            # value if static.
            'value',
            # make an array of arrays if multivalued.
            'multivalued'];
        if (!is_array($yaml)) {
            # Can only generate structures from yaml arrays, a string is not correct.
            throw new \Exception("Not a Yaml array");
        }

        $obj = new MetadataElement($name);
        $fields = array_keys($yaml);
        $process = array_diff($fields, $specialFields);
        $twig_field = $yaml['twigVar'];
        if (isset($twig_field)) {
            $obj->setTwigField($twig_field);
        }
        if (isset($yaml['attributes'])) {
            foreach ($yaml['attributes'] as $name => $attrib) {
                if (isset($attrib['value'])) {
                    // Is a literal
                    $obj->addAttribute($name, $attrib['value']);
                }
            }
        }
        if (isset($yaml['value'])) {
            // Literal field
            $obj->setValue($yaml['value']);
        }
        if (isset($yaml['multivalued'])) {
            if ((bool)$yaml['multivalued']) {
                $obj->setMultiValued();
            }
        }
        foreach ($process as $tagName) {
            $tag = $yaml[$tagName];
            if (isset($tag['reference'])) {
                # Reference to another column in the record.
                if (!$this->metadataMgr->hasField($tag['reference'])) {
                    $this->metadataMgr->addField($tag['reference'], $this->parseYamlStruct($tag['reference'], $this->yaml[$tag['reference']]));
                }
                $child = $this->metadataMgr->getField($tag['reference']);
            } elseif (isset($tag['value'])) {
                # A literal child, so store it here only.
                $child = new MetadataElement($tagName);
                $child->setTwigField($tagName);
                $child->setValue($tag['value']);
            }
            if (isset($child)) {
                $obj->addChild($child);
                unset($child);
            }
        }
        return $obj;
    }
}
