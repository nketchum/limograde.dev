<?php

/**
 * @file
 * Contains \DrupalProject\composer\SubthemeHandler.
 */

namespace DrupalProject\composer;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class SubthemeHandler {

  public static function createSubtheme(Event $event) {

    // Alert start.
    $event->getIO()->write("Attempting to create a Bootstrap-Sass subtheme...");

    // Optional subtheme_name cli arg.
    count($event->getArguments()) ? ($subtheme_name = $event->getArguments()[0]) : ($subtheme_name = 'bootstrap_sass');

    // Variable definitions.
    $theme_name        = 'bootstrap';
    $theme_subdir      = 'starterkits/sass';
    $bootsass_git_vtag = '3.3.7';
    $root_dir          = getcwd() .'/web';
    $contrib_dir       = $root_dir .'/themes/contrib';
    $custom_dir        = $root_dir .'/themes/custom';
    $theme_dir         = $contrib_dir .'/'. $theme_name;
    $subtheme_dir      = $custom_dir .'/'. $subtheme_name;

    // Test filesystem constraints.
    $fsys = new Filesystem();
    if (!$fsys->exists($theme_dir))     exit($event->getIO()->writeError("Error: \"$theme_name\" base theme not found in $theme_dir."));
    if ($fsys->exists($subtheme_dir))   exit($event->getIO()->writeError("Error: Subtheme \"$subtheme_name\" already exists in $subtheme_dir."));
    if ($subtheme_name === $theme_name) exit($event->getIO()->writeError("Error: Name \"$subtheme_name\" clashes with existing theme \"$theme_name\"."));

    // Duplicate the starterkit.
    $fsys->mirror($theme_dir .'/'. $theme_subdir, $subtheme_dir);

    // Rename starterkit theme files.
    $fsys->rename("$subtheme_dir/THEMENAME.theme", "$subtheme_dir/$subtheme_name.theme");
    $fsys->rename("$subtheme_dir/THEMENAME.starterkit.yml", "$subtheme_dir/$subtheme_name.info.yml");
    $fsys->rename("$subtheme_dir/THEMENAME.libraries.yml", "$subtheme_dir/$subtheme_name.libraries.yml");
    $fsys->rename("$subtheme_dir/config/install/THEMENAME.settings.yml", "$subtheme_dir/config/install/$subtheme_name.settings.yml");
    $fsys->rename("$subtheme_dir/config/schema/THEMENAME.schema.yml", "$subtheme_dir/config/schema/$subtheme_name.schema.yml");

    // Replace placeholders in those files.
    $find = new Finder();
    foreach ($find->files()->contains('/THEMETITLE|THEMENAME/')->in($subtheme_dir) as $file) {
      $contents = $file->getContents();
      $contents = preg_replace('/THEMETITLE/', $subtheme_name, $contents);
      $contents = preg_replace('/THEMENAME/', $subtheme_name, $contents);
      $filepath = $subtheme_dir .'/'. $file->getRelativePathname();
      $fsys->dumpFile($filepath, $contents);
    }

    // Commanding forth the bootstrap-sass remote repo.
    $cmds  = '';
    $cmds .= "curl -o $subtheme_dir/bootstrap.zip -L https://github.com/twbs/bootstrap-sass/archive/v$bootsass_git_vtag.zip;";
    $cmds .= "unzip $subtheme_dir/bootstrap.zip -d $subtheme_dir;";

    // Execute commands via a process.
    $proc = new Process($cmds);
    $proc->run();

    // Post-process renaming+cleaning.
    if ($proc->isSuccessful()) {
      $fsys->rename("$subtheme_dir/bootstrap-sass-$bootsass_git_vtag", "$subtheme_dir/bootstrap");
      $fsys->remove("$subtheme_dir/bootstrap.zip");
    } else {
      $event->getIO()->writeError("Warning: Bootstrap-Sass library extraction process did not execute properly.");
    }

    // Alert success.
    $event->getIO()->write("Success! Created \"$subtheme_name\" in $subtheme_dir using \"$theme_name\".");

  }

}
