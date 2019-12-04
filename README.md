This is a Symfony Console command that converts a Tab-separated spreadsheet to an XML file. 
Currently there is a sample MODS and PBCore template provided.

How it works:
To use the module, you need two files as well as this module installed correctly:

1) A file, tab separated, with the first row being a representation of the field type
   ("title","access date","contributor", etc) followed by each subsequent row representing
   an item

2) A YAML file broken into 2 or more sections. One section is only 1 line that informs the
   module which field, if any, to use as the output filename for the records. A second
   section is used to connect the header fields to the metadata field, as well as
   allowing the use of an attribute with the field.
   
3) You can create your own [Twig](https://twig.symfony.com/) template and use it.

The output of the module can either be individual metadata records written to a given
directory or a zip file that contains the metadata records.

### Installation
```bash
git clone https://github.com/robyj/csv2meta
cd csv2meta
composer install
```

### Usage
The correct usage of the command is described with the `--help` argument
```bash
> ./bin/console csv2meta --help
Description:
  Convert CSV to metadata.

Usage:
  csv2meta [options] [--] <csvfile> <yamlfile> <directory>

Arguments:
  csvfile                      Path to the csv file to use as metadata source
  yamlfile                     Path to the YAML configuration file.
  directory                    Path to output directory

Options:
  -t, --templatePath[=TEMPLATEPATH]  The full path to the twig template to use [default: "mods.twig"]
  -z, --zipfile=ZIPFILE              File name to output zipfile
  -d, --delimiter=DELIMITER          Delimiter [default: "\t"]
  -x, --extension[=EXTENSION]        The extension to use for output files. [default: "txt"]
  -h, --help                         Display this help message
  -q, --quiet                        Do not output any message
  -V, --version                      Display this application version
      --ansi                         Force ANSI output
      --no-ansi                      Disable ANSI output
  -n, --no-interaction               Do not ask any interactive question
  -v|vv|vvv, --verbose               Increase the verbosity of messages: 1 for normal output, 2 for more 
                                     verbose output and 3 for debug
```

The `--templatePath` is a full path to your own twig templates, any templates referenced from the this one must be 
located in the same directory. 

If you specify a filename and it doesn't exist in the current working directory, we try the internal `templates/` path.

ie. 
```bash
./bin/console csv2meta -t mods.twig test_metadata.tsv config.yaml.dist ../someOutputDirectory
``` 
This will use the internal `templates/mods.twig` template unless you create a template in the current path with the
same name. 

Using the `config.yaml.dist` example configuration file and the provided `test_metadata.tsv` run:
```bash
./bin/console csv2meta test_metadata.tsv config.yaml.dist ../someOutputDirectory
```

Will output 2 files in the MODS format.
