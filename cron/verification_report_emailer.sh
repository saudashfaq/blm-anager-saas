#!/bin/bash

# Change to the project directory
cd /Applications/MAMP/htdocs/backlinks_manager_saas

# Run the PHP script
/usr/bin/php jobs/VerificationReportEmailer.php >> /var/log/backlinks_manager/verification_reports.log 2>&1 