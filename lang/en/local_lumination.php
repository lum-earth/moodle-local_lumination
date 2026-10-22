<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * English language strings for the Lumination AI plugin.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['action_create_course'] = 'Create course';
$string['action_generate_lesson'] = 'Generate lesson';
$string['action_generate_outline'] = 'Generate outline';
$string['action_upload_document'] = 'Upload document';
$string['addlesson'] = 'Add lesson';
$string['addmodule'] = 'Add module';
$string['category'] = 'Course category';
$string['coursegenerator'] = 'AI Course Generator';
$string['coursegenerator_desc'] = 'Upload documents and generate a full Moodle course structure using Lumination AI.';
$string['coursegenerator_nav'] = 'AI Course Generator';
$string['createcourse'] = 'Create Moodle course';
$string['creatingcourse'] = 'Creating your Moodle course...';
$string['errorapiconnect'] = 'Could not connect to the AI Tutor API at {$a->host} ({$a->detail}). Check the API base URL in the plugin settings and that this server can reach the internet. Request ID: {$a->requestid}';
$string['errorapicredits'] = 'Your AI Tutor API key has run out of credits (HTTP 402). Buy credits at https://ai-tutor.ai/dashboard/api and try again. Request ID: {$a->requestid}';
$string['errorapifailed'] = 'Lumination API request failed: {$a}';
$string['errorapihttp'] = 'The AI Tutor API at {$a->host} returned HTTP {$a->code}: {$a->detail}. Request ID: {$a->requestid}';
$string['errorapijob'] = 'The AI Tutor API could not complete the request: {$a->detail}. Request ID: {$a->requestid}';
$string['errorapikey'] = 'The AI Tutor API at {$a->host} rejected the API key (HTTP {$a->code}: {$a->detail}). Open Test connection on the plugin settings page to see which key Moodle is using and compare it with the key in https://ai-tutor.ai/dashboard/api. Also check that the API base URL is https://ai-tutor.ai/api/v1. Request ID: {$a->requestid}';
$string['errornoapi'] = 'The Lumination AI Course Generator is not configured. Add your AI Tutor API key in the plugin settings.';
$string['errornocontent'] = 'No content was returned from the API.';
$string['generateoutline'] = 'Generate outline';
$string['generatingcourse'] = 'Generating your course...';
$string['generatingcourse_desc'] = 'Saving your outline and starting course generation.';
$string['generatingoutline'] = 'Generating course outline from your documents...';
$string['hubinuse'] = 'Using the Lumination AI Provider\'s key';
$string['hubinuse_desc'] = 'This site has the Lumination AI Provider enabled, so the Course Generator uses its API key and base URL, and the two fields below are ignored. Manage the key in <a href="{$a}">AI providers</a>.';
$string['instructions'] = 'Instructions';
$string['instructions_help'] = 'Optional guidance for course generation (e.g. audience level, tone, scope).';
$string['jobcanleave'] = 'You can leave this page. Generation continues in the background, and this page refreshes every few seconds.';
$string['jobcompleted'] = 'The course "{$a->name}" is ready, with {$a->total} lessons.';
$string['jobcompletedwithfailures'] = 'The course "{$a->name}" was created, but {$a->failed} of {$a->total} lessons could not be generated and contain placeholder text. Edit those pages to add the content.';
$string['jobfailed'] = 'Generating the course "{$a->name}" failed.';
$string['jobprogress'] = 'Course generation';
$string['jobqueued'] = 'The course "{$a->name}" ({$a->total} lessons) is queued and will start shortly.';
$string['jobrunning'] = 'Generating "{$a->name}": {$a->done} of {$a->total} lessons done.';
$string['jobwaitingcron'] = 'This job has not started yet. Course generation runs in the background through Moodle\'s cron, so ask your site administrator to check that cron is running (Site administration > Server > Tasks).';
$string['keyhint'] = '{$a->start}...{$a->end} ({$a->length} characters)';
$string['language'] = 'Language';
$string['lessonname'] = 'Lesson';
$string['lumination:generatecourse'] = 'Generate courses using Lumination AI';
$string['lumination:manage'] = 'Manage Lumination AI settings';
$string['lumination:viewusage'] = 'View Lumination AI usage statistics';
$string['modulename'] = 'Module';
$string['outlinereview'] = 'Review course outline';
$string['outlinereview_desc'] = 'Review and edit the generated outline before creating the course.';
$string['pluginname'] = 'Lumination AI Course Generator';
$string['privacy:metadata:api'] = 'Document content uploaded by users is sent to the Lumination API for text extraction and AI-driven course generation.';
$string['privacy:metadata:api:document_content'] = 'The content of documents uploaded by the user, sent for text extraction and course outline generation.';
$string['privacy:metadata:documents'] = 'Records of documents uploaded to the Lumination API for course generation.';
$string['privacy:metadata:documents:document_uuid'] = 'The unique identifier assigned to the document by the Lumination API.';
$string['privacy:metadata:documents:filename'] = 'The original filename of the uploaded document.';
$string['privacy:metadata:documents:timecreated'] = 'The date and time the document was uploaded.';
$string['privacy:metadata:documents:userid'] = 'The ID of the user who uploaded the document.';
$string['privacy:metadata:jobs'] = 'Records of background course generation jobs and their progress.';
$string['privacy:metadata:jobs:coursename'] = 'The name of the course being generated.';
$string['privacy:metadata:jobs:status'] = 'The status of the job.';
$string['privacy:metadata:jobs:timecreated'] = 'The date and time the job was started.';
$string['privacy:metadata:jobs:userid'] = 'The ID of the user who started the job.';
$string['privacy:metadata:usage'] = 'Records of API calls made to the Lumination API, tracking token and credit consumption.';
$string['privacy:metadata:usage:action'] = 'The type of API action performed (e.g. generate_outline, generate_lesson).';
$string['privacy:metadata:usage:credits'] = 'The number of credits charged for the API call.';
$string['privacy:metadata:usage:timecreated'] = 'The date and time the API call was made.';
$string['privacy:metadata:usage:tokens_in'] = 'The number of input tokens consumed by the API call.';
$string['privacy:metadata:usage:tokens_out'] = 'The number of output tokens consumed by the API call.';
$string['privacy:metadata:usage:userid'] = 'The ID of the user who triggered the API call.';
$string['removelesson'] = 'Remove lesson';
$string['removemodule'] = 'Remove module';
$string['setting_apikey'] = 'API Key';
$string['setting_apikey_desc'] = 'Requires an AI Tutor API key (it starts with sk-tutor-). Each generated lesson costs about $0.02, roughly $0.30 for a 20-lesson course, and new accounts get $20 of free credits (about 60 such courses). Register at https://ai-tutor.ai and create a key at https://ai-tutor.ai/dashboard/api.';
$string['setting_baseurl'] = 'API base URL';
$string['setting_baseurl_desc'] = 'The AI Tutor API base URL (includes the /api/v1 prefix). Defaults to production; change only for a different environment.';
$string['testconnection'] = 'Test connection';
$string['testconnection_back'] = 'Back to settings';
$string['testconnection_baseurl'] = 'API base URL in use: {$a}';
$string['testconnection_fromhub'] = 'The key and base URL come from the Lumination AI Provider.';
$string['testconnection_key'] = 'API key in use: {$a}';
$string['testconnection_keyformat'] = 'The API key does not start with sk-tutor-, so it is probably a key from the old Lumination API. Create a new key at https://ai-tutor.ai/dashboard/api.';
$string['testconnection_keyincomplete'] = 'The API key does not look complete. AI Tutor keys are sk-tutor- followed by 48 letters and numbers. If the dashboard shows the key shortened or hidden (with ... or dots), create a new key and copy it in full when it is shown.';
$string['testconnection_link'] = 'Test the API key and base URL';
$string['testconnection_notapi'] = 'The server at {$a->host} answered with HTTP {$a->code}, but it does not look like the AI Tutor API ({$a->detail}). Check the API base URL; it should be https://ai-tutor.ai/api/v1. Request ID: {$a->requestid}';
$string['testconnection_ok'] = 'Connected to {$a->host}. The API key was accepted.';
$string['uploadfiles'] = 'Source document';
$string['uploadfiles_help'] = 'Upload a PDF, Word, or text file containing the course material. The outline is generated from this document.';
$string['uploadtitle'] = 'Course title';
$string['uploadtitle_help'] = 'A title for the course to be generated.';
$string['usage'] = 'API Usage';
$string['usage_action'] = 'Action';
$string['usage_by_action'] = 'Usage by action';
$string['usage_by_user'] = 'Top users';
$string['usage_credits'] = 'Credits';
$string['usage_daily'] = 'Daily breakdown';
$string['usage_date'] = 'Date';
$string['usage_days'] = '{$a} days';
$string['usage_desc'] = 'View Lumination AI API usage statistics including tokens consumed and credits charged.';
$string['usage_nav'] = 'API Usage Dashboard';
$string['usage_nodata'] = 'No usage data for this period.';
$string['usage_period'] = 'Period';
$string['usage_requests'] = 'Requests';
$string['usage_tokens_in'] = 'Tokens in';
$string['usage_tokens_out'] = 'Tokens out';
$string['usage_total'] = 'Total';
$string['usage_user'] = 'User';
$string['viewcourse'] = 'View course';
