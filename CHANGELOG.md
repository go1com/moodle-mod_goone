# Changelog

All notable changes to the GO1 activity module (mod_goone) are in this file.

## [5.3-rc.0] - 2026-10-28

Changes since the release MOODLE403_2024040500.

### Security

- Fixed 13 security issues: 2 critical, 3 high, 7 medium and 1 low.

### Added

- Course reset support. The course reset form has the option "Delete GO1 progress and completion data". The new site setting "On course reset" sets the default of this option.
- The "Create or repair webhook" button. It repairs the GO1 webhook of this site with a new secret, asks for confirmation, and shows the result on the settings page. More than one Moodle site can now use the same GO1 portal.
- The setting "Keep webhook log rows for" (default 90 days) and a daily task that deletes older log rows. The webhook log page shows 100 rows on each page.
- The webhook writes a log row for each change that it makes to an activity.
- The privacy provider declares the GO1 user map, the webhook log and the data that goes to GO1. Data requests export and delete the user map and the log rows of the user.

### Changed

- Supports Moodle 4.5 to 5.3. Requires Moodle 4.5 or later.
- An activity completes when the GO1 enrolment has the status "completed". The plugin no longer uses the "pass" value of the enrolment.
- The completion task runs once a day at a random time by default. An administrator's schedule does not change.
- The webhook processes only "enrollment.complete" events. It ignores all other events and events without the necessary data.
- The webhook returns an error status (401 or 503) when it cannot process an event, so that GO1 sends the event again. A webhook request does not start a Moodle session.
- The completion rule on the activity page now says "Finish the learning object".

### Fixed

- Moodle removed a recorded GO1 completion after the GO1 player sent a progress update, or after a manual override. The activity and the course then became incomplete.
- One bad enrolment stopped the completion task for all the activities after it. The task read only the first 20 completed enrolments of each learning object.
- Matching of GO1 users to Moodle users. Deleted and suspended users, duplicate emails, empty emails and a second GO1 user for the same Moodle user gave incorrect matches or errors.
- The content browser on Moodle 5.0 and later: the filters did not open, the layout was incorrect, and the "Add" menu of the bulk content browser did not open.
- A keyword with a space gave no results in the bulk content browser. Both content browsers now show a message when the request to GO1 fails.
- A JavaScript error in the content browser when the GO1 API does not answer, for example with a token that is not valid.
- Backup and restore: GO1 completion records, the activity description and its files are now included. Deleting an activity also deletes its completion records.
- The completion settings of the plugin were missing from the default and the bulk completion forms.
