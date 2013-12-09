Postmaster-SendGrid-EE2-Mailing-List-Service
============================================

Send e-mails to ExpressionEngine Mailing Lists subscribers with Postmaster and SendGrid.

![Service screenshot](https://www.mpasqualone.com/assets/img/github/Postmaster_sendgrid_ee2_ml.png)

Installation
------------
Move `Sendgrideemailinglist.php` into `third_party/postmaster/services/`

Notes
-----
This Service uses the [x-smtpapi](http://sendgrid.com/docs/API_Reference/Web_API/mail.html) field to send to multiple receipients. The 'to' field on line 95 isn't used, however I recommend you change it to a real e-mail address just in case SendGrid ever choose to stop ignoring it. Currently it's set to `dummy@example.com`.
