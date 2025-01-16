# postfix-limiter
### Simple script to limit total number of mails sent to outside domains
In the beginning of postfix_limiter.php there are settings.
postfix_limiter.service can be placed in /etc/systemd/system and act as a service, so that it will work at startup.

To apply this limiter to postfix edit main.cf in a following way:

    
    smtpd_relay_restrictions =
            check_policy_service inet:127.0.0.1:10031,

This has to be the first line after smtpd_relay_restrictions (in order to apply to to all requests)
