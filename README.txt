SITE PERFORMANCE ADMIN PLUGIN
=================================

Site performance is a Moodle 2.X admin plugin that allow administrators to check system performance. 
Data is shown on a graph and can be filtered by year, week, day and hour.   
   
Web: https://github.com/IOC/moodle-admin-siteperf


Install
=======

Download code from gitHub, and copy inside directory MOODLE_PATH/admin/tool/siteperf

Custom modifications to Moodle 2.3 source code:

 - lib/outputrenderers.php inside footer function (line 799):
 
    tool_siteperf::shutdown();
    
 - lib/setup.php under require_once($CFG->libdir .'/setuplib.php'); (line 398):
 
    require_once($CFG->dirroot . '/admin/tool/siteperf/lib.php');
    tool_siteperf::init();
    
 - lib/weblib.php inside redirect function (line 2446):
 
    tool_siteperf::shutdown(); 


jQuery & jqPlot
===============

Site performace requires jQuery and jqPlot libraries. You can dowload from:

jQuery: http://jquery.com/
jqPlot: http://www.jqplot.com/


Author
======

Marc Català Sala <mcatala@ioc.cat>
Albert Gasset Romo <albert.gasset@gmail.com>


Copyright
=========

Copyright © 2012 Institut Obert de Catalunya

Site performance plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Site performance plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
