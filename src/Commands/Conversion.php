<?php

namespace robyj\csv2meta\Commands;

/**
 * @csv2meta.module
 *
 * Module to convert a CSV file to metadata using YAML to control
 * how that would happen
 */

use robyj\csv2meta\Objects\MetadataElement;
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

    private $yaml;

    private $metadataFields;

    private $twig;

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

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (!is_null($input->getArgument('yamlfile'))) {
            $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../templates');
            $this->twig = new \Twig\Environment($loader);
            $yamlname = $input->getArgument('yamlfile');
            // read in the YAML glue file
            $yaml = Yaml::parseFile($yamlname);
            //$yaml = \yaml_parse_file($yamlname, -1);
            if ($yaml === false) {
                $output->setDecorated(true);
                $output->writeln("ERROR parsing Yaml file ({$yamlname})");
                $output->setDecorated(false);
                exit(1);
            }
            $this->yaml = $yaml;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $header = "";

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

            $this->metadataFields = [];

            foreach ($header as $item) {
                if (array_key_exists($item, $this->yaml)) {
                    $this->metadataFields[$item] = $this->parseYamlStruct($item, $this->yaml[$item]);
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
                    if ($header[$cnt] == $this->yaml["filename"]) {
                        $filename = $line[$cnt];
                    }

                    $mdval = $line[$cnt];
                    if (array_key_exists($headval, $this->metadataFields)) {
                        $this->metadataFields[$headval]->setValue($mdval);
                    }
                }

                $dataArray = [];
                foreach ($this->metadataFields as $field) {
                    $dataArray = array_merge_recursive($dataArray, $field->getDataArray());
                }
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


    private function parseYamlStruct($name, $yaml)
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
                } elseif (isset($attrib['reference'])) {
                    // Is a reference, need to pull it in here.
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
            if (isset($tag['value'])) {
                $child = new MetadataElement($tagName);
                $child->setValue($tag['value']);
            }
            if (isset($tag['reference'])) {
                if (!isset($this->metadataFields[$tag['reference']])) {
                    $this->metadataFields[$tag['reference']] = $this->parseYamlStruct($tag['reference'], $this->yaml[$tag['reference']]);
                }
                $obj->addChild($this->metadataFields[$tag['reference']]);
            }
        }
        return $obj;
    }
}
