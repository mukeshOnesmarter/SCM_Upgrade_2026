# FLEXEE Supply Chain Management (development branch)

The Laravel 13 rebuild of FLEXEE 1.0 SCM. The design is SCM_V3_Upgrade_Design_Note.md in the "CF Manufacturing Rebuild" project. The old code (main, and the live site scm.flexee.org) is the reference: the rebuild must reproduce its recorded figures (scm_baseline_v1.json) unless a decision says otherwise.

## Stack

- PHP 8.4, Laravel 13, MySQL 8.0.
- Email through Mailjet's Send API (`MAIL_MAILER=mailjet`).
- Queue on the database. Cron runs `php artisan schedule:run` every minute, and the schedule starts one queue worker at a time (routes/console.php).
- Hosted on GoDaddy shared hosting at https://development.flexee.org. GitHub Actions tests every push and deploys the `development` branch (.github/workflows/development.yml).

## Rules

- Vikram owns the product and decides. Mukesh builds and pushes. Claude writes the instructions and checks each push.
- Claude never pushes, merges or deploys.
- Never commit `.env`, passwords, API keys, tokens, SSH keys, database dumps or server notes. Never paste `.env` contents into a chat or a document.
- Every change comes with tests. CI runs them against MySQL 8.0, with Pint for code style.
- Store times in UTC. Show them in the course's own time zone, with its abbreviation.
- Student-facing words are plain English that reads right the first time: no code keys as labels.
