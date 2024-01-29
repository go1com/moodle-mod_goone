# Go1 Module

Go1 is a content company and as such they provide a content library of learning
resources that, with help from this plugin, can be added to any new or existing
Moodle course to suit your training needs. Go1 provides interactive content,
videos, documents and full-length, multi-component courses. Topics covered by
Go1 include professional development, compliance, soft skills and more.

This plugin allows Go1 content to be accessed directly in a Moodle instance.

## Requirements

- A Moodle derived LMS (for example Open LMS).

## How to install

The name of the directory that contains the files from this plugin should be
called 'goone' (rename it if necessary). This directory must be placed directly
under the 'mod' directory from your Moodle derived LMS, so a possible path
would be 'moodle/mod/goone/'.

Next, go to your Moodle derived LMS website and login as an admin user. It
should automatically prompt you to install the Go1 plugin. Follow the steps
presented by the website.

## How it works

The Go1 plugin has several useful features, such as the following:

   - Connection between your LMS and your Go1 account.
   - Access to a large eLearning library, with courses for compliance,
   professional development, personal growth, and more. The specific courses
   available to you depend on your Go1 account.
   - Go1 courses can be added as Activity Modules into Courses in your LMS, so
   they can be treated as regular Activity Modules (for example they can have
   completion tracking, they can be graded, etcetera).
   - You can configure the set of admin users that can change the Go1 settings.

## How to Use it

You will need to connect your Go1 account to Moodle from the Go1 plugin
administration settings page, so you should already have a Go1 account. 

### Configuration

In the Go1 settings page (which can be accessed by following the breadcrumb
'Home -> Site administration -> GO1'), click on the 'Retrieve GO1 credentials'
button, and follow the instructions to obtain the Client ID and the Client
secret to link your Go1 account to your LMS. Ultimately you must copy the
Client ID and the Client secret in their respective fields of the Go1 settings
page, and then click on 'Save changes'.

### Basic usage

Follow these steps to create a Go1 Activity Module.

   1. Login as an editing teacher (or any user with a role that can create
   Activity Modules, such as an admin).
   2. Go to the course where you will create the Go1 Activity Module (or create
   the course if it does not exist). Then go to the section where you want to
   create the Go1 Activity Module.
   3. Create a new Activity Module (this step depends on the theme of your LMS,
   like Snap or Boost).
   4. Select 'GO1 content item' from the list of Activity Modules.
   5. Input the fields to fill in the form. To choose a specific Go1 course,
   click on 'Open GO1 Content Browser', this will open a new window where you
   can browse Go1 courses. Select the course of your choosing.
   6. When you are done, click on 'Save and return to course'.

Follow these steps to use a Go1 Activity Module.

   1. Go to the course and section where you have created the Go1 Activity
   Module.
   2. Click on the Go1 Activity Module name to access to it.
   3. Use the activity like a regular activity (start it, study it, complete
   it, exit from it, etcetera).

## Flags

### The `mod_goone_admin_users` flag

The purpose of this flag is to determine the set of admin users that will be
capable of accessing and setting the Go1 settings.

This flag is set as an array:

   - `$CFG->mod_goone_admin_users = ['admin1', 'admin2'];` means that only the
   admins 'admin1' and 'admin2' will be capable of changing the Go1 settings.
   The rest of the users can only read the Client ID and the Client secret from
   Go1 when accessing the Go1 settings. Not setting this flag has the opposite
   effect of allowing any admin user to change the Go1 settings.

## License

Copyright (c) 2021 Open LMS (https://www.openlms.net)

This plugin was developed by eCreators PTY (https://ecreators.com.au/) and
Open LMS for Go1.com (https://go1.com/).

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.