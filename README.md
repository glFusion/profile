profile
=======

Custom Profile plugin for glFusion
Release 1.0.0

The Custom Profile plugin for glFusion allows the administrator to create 
custom profile elements for user acccounts.  Prevously, this required 
significant cusom programming and template modification.

Although some templates still need to be customized, and a few functions 
added to lib-custom.php, this only needs to be done once.  After the initial 
setup, custom fields can be created and removed at will with no further 
site customization and no database modifications.

NOTE: Beginning with glFusion 1.1.7, no template modifications are needed.
However, the CUSTOM_ functions form lib-custom.php will be used if they're
available.

If you've made additional customizations and  need to modify the templates 
manually, the affected templates are:
    {layout_dir}/custom/memberdetail.thtml
    {layout_dir}/users/custom/profile.thtml
    {layout_dir}/admin/user/custom/edituser.thtml

Please feel free to distribute this program.  However you must include the 
copyright and license information.  Please don't remove the copyright 
information within the page unless authorized.  Copyrights information in 
acceptable form is allowed.

   
Copyright (C) 2009 by Lee Garner <lee@leegarner.com>

LICENSE:

This program is free software; you can redistribute it and/or modify it 
under the terms of the GNU General Public License as published by the 
Free Software Foundation; either version 2 of the License, or (at your option) 
any later version.

This program is distributed in the hope that it will be useful, but WITHOUT 
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS 
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with 
this program; if not, write to the 
    Free Software Foundation, Inc., 
    59 Temple Place, 
    Suite 330, Boston, MA 02111-1307 
    USA

