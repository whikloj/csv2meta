<?php

/**
 * @csv2meta.drush.inc
 *
 * drush interface for the csv2meta module
 * (It converts CSV to metadata with a generous helping of YAML)
 */

function csv2meta_drush_command() {
  
  $items = array();
  $items['csv2meta'] = array(
    'aliases' => array('csv2meta'),
    'description' => 'Import a CSV file into a metadata standard',
    'options' => array(
      'csvfile' => array(
        description => 'path to the csv file to usse as a source',
        required => TRUE,
      ),
      'yamlfile' => array(
        description => 'path to a YAML glue file',
        required => TRUE,
      ),
      'metadata' => array(
        description => 'Metadata standard to use, MODS by default',
        required => FALSE,
      ),
      'zipfile' => array(
        description => 'resultant zipfile containing all metadata recordss',
        required => FALSE,
      ),
      'outdir' => array(
        description => 'directory to output metadata records to',
        required=> FALSE,
      ),
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN,
  );
  return $items;
}

function drush_csv2meta() {

  $usezip = FALSE;
  drupal_static_reset('islandora_get_tuque_connection');
  $connection = islandora_get_tuque_connection();
  $csv = drush_get_option('csvfile');
  $yaml = drush_get_option('yamlfile');
  $metadata = drush_get_option('metadata','MODS');
  if (drush_get_option('outdir')) {
    $outdir = drush_get_option('outdir');
    $zip = None;
  } else {
    $outdir = file_directory_temp();
    $zip = drush_get_option('zipfile');
    $usezip = TRUE;
  }
  csv2meta_process($csv,$yaml,$outdir,$zip);
}

?>
