**THIS PLUGIN IS IN BETA VERSION AND NOT RECOMMANDED TO USE IN PRODUCTION SITES**

Introduction
============

A plagiarism plugin for Moodle 2.x. The anti-plagiarism engine is [Moss](http://theory.stanford.edu/~aiken/moss/) which can detect plagiarism in source code and plain text files. Supports C, C++, Java, C#, Python, Visual Basic, Javascript, FORTRAN, ML, Haskell, Lisp, Scheme, Pascal, Modula2, Ada, Perl, TCL, Matlab, VHDL, Verilog, Spice, MIPS assembly, a8086 assembly, a8086 assembly, MIPS assembly and HCL2.


Prerequisite
============

On Linux
--------

* Moodle 2.0 or above
* perl

On Windows
----------

* Moodle 2.0 or above
* Cygwin with perl


Download
========

Download it from https://github.com/hit-moodle/moodle-plagiarism_moss/archives/master

or using git:

`git clone git://github.com/hit-moodle/moodle-plagiarism_moss.git moss`


Installation & Upgrading
========================

*MOODLE_PATH means the root path of your moodle installation.*

On Linux
--------

1. If the directory `MOODLE_PATH/plagiarism/moss/` exists, remove it.
2. Make sure the directory name of this plugin is `moss`. If not, rename it.
3. Put `moss` into `MOODLE_PATH/plagiarism/`.
4. Login your site as admin and the plugin will be installed/upgraded.
5. Access `http://YOUR.MOODLE.DOMAIN/admin/settings.php?section=optionalsubsystems` to enable plagiarism.
6. Access `http://YOUR.MOODLE.DOMAIN/plagiarism/moss/settings.php` to enable & setup.

On Windows
----------

1. If the folder `MOODLE_PATH\plagiarism\moss\` exists, remove it.
2. Make sure the folder name of this plugin is `moss`. If not, rename it.
3. Put `moss` into `MOODLE_PATH\plagiarism\`.
4. Login your site as admin and the plugin will be installed/upgraded.
5. Access `http://YOUR.MOODLE.DOMAIN/admin/settings.php?section=optionalsubsystems` to enable plagiarism.
6. Access `http://YOUR.MOODLE.DOMAIN/plagiarism/moss/settings.php` to enable & setup.


Usage
=====

First, make sure the cron job of your moodle works well. Check <http://docs.moodle.org/20/en/Cron> for details.

In the activity setting page of any moodle module which supports plagiarism API (now, assignment only), there should be settings for Moss Anti-Plagiarism. Follow the inline help and enjoy it.


Links
=====

Home:

* <https://github.com/hit-moodle/moodle-plagiarism_moss>

Bug reports, feature requests, help wanted and other issues:

* <https://github.com/hit-moodle/moodle-plagiarism_moss/issues>
