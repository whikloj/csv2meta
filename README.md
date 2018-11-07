This is a drupal 7.x module that can be called from Drush and allows a CSV formatted
spreadsheet to be converted to a metadata entity (as of version 1.0, MODS only.
Other standards such as PBCORE may be added).


How it works:
To use the module, you need two files as well as this module installed correctly:

1) A CSV file, tab separated, with the first row being a representation of the field type
   ("title","access date","contributor", etc) followed by each subsequent row representing
   an item

2) A YAML file broken into 2 or more sections. One section is only 1 line that informs the
   module which field, if any, to use as the output filename for the records. A second
   section is used to connect the CSV header fields to the metadata field, as well as
   allowing the use of an attribute with the field. A third section, which is optional
   and very site specific, gives what I call "fixed fields" and these are fields that are
   to be injected into the metadata record but aren't represented by CSV data. I use these
   generally as placeholders in the metadata record that are filled in further along in
   the ingest procedure. They could also be used for site specific data such as license
   records, URLs, any data that has to be added to all records.

The output of the module can either be individual metadata records written to a given
directory or a zip file that contains the metadata records.

Prerequisites:
YAML (Available via PECL)


Usage:
drush csv2meta --csvfile=/path/to/csv/file --yamlfile=/path/to/yaml/file [--outdir=/out/dir/path] [--zipfile=/path/to/zip/file]

NOTES:
1) data in a cell the spreadsheet can be separated by a value to allow the cell to be exploded and each part of the cell to be added as a field. By default the value is "--" and it can be modified in the metadataobj.inc file. Why is this feature useful? I've found in the past that spreadsheet values for MODS subject topics are given as multiple values in the same cell. So a feature to split a string and add each part as a element is useful.

2) It seems that only one attribute can be used per element. I'm not sure why this is.



