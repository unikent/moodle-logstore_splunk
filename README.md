# Splunk log store

This plugin syncs Moodle's logs to Splunk via the API.
It can be done either in realtime (as the logs are entered) or as a background cron task.

The cron task can be useful if you are just trialing Splunk or do not otherwise have a proper HA setup.
