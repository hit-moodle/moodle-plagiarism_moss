**THIS PLUGIN IS IN BETA VERSION AND NOT RECOMMANDED TO USE IN PRODUCTION SITES**

Introduction
============

A plagiarism plugin for Moodle 2.x. The anti-plagiarism engine is [Moss](http://theory.stanford.edu/~aiken/moss/) which can detect plagiarism in source code and ASCII text. Supports C, C++, Java, C#, Python, Visual Basic, Javascript, FORTRAN, ML, Haskell, Lisp, Scheme, Pascal, Modula2, Ada, Perl, TCL, Matlab, VHDL, Verilog, Spice, MIPS assembly, a8086 assembly, a8086 assembly, MIPS assembly and HCL2.


Download
========

Download it from https://github.com/hit-moodle/moodle-plagiarism_moss/archives/master

or using git:

`git clone git://github.com/hit-moodle/moodle-plagiarism_moss.git moss`


Installation & Upgrading
========================

*MOODLE_PATH means the root path of your moodle installation.*

1. If the directory `MOODLE_PATH/plagiarism/moss/` exists, remove it.
2. Make sure the directory name of this plugin is `moss`. If not, rename it.
3. Put `moss` into `MOODLE_PATH/plagiarism/`.
4. Login your site as admin and the plugin will be installed/upgraded.
5. Access `http://YOUR.MOODLE.DOMAIN/admin/settings.php?section=optionalsubsystems` to enable plagiarism.
6. Access `http://YOUR.MOODLE.DOMAIN/plagiarism/moss/settings.php` to enable & setup.


Usage
=====

In the activity setting page of any moodle module which supports plagiarism API (now, assignment only), there should be settings for Moss Anti-Plagiarism. Follow the inline help.


Links
=====

Home:

* <https://github.com/hit-moodle/moodle-plagiarism_moss>

Bug reports, feature requests, help wanted and other issues:

* <https://github.com/hit-moodle/moodle-plagiarism_moss/issues>
