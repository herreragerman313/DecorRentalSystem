# DecorRentalSystem

Website for **Zero Waste Event Design Services**, our ENT 310 business at CSUN
(Zayli Tellez, Neria Arinda, Joshua Mineros, and German Herrera). Built with PHP,
MySQL, HTML, and CSS, and also used as practice for my CIT 480 senior project.

The business replaces throwaway party decorations with stylish, reusable ones.
We rent reusable decor only (no selling), with setup and takedown as an optional
paid add-on. Our target customer is people aged 18 to 45 who like decorating for
events and want a more sustainable option than buying single use decor.

## Features

**Customers**
- Sign up and log in
- Browse decor by category, search, and event date with live availability
- Themed packages for quinceañeras, weddings, birthdays, and holidays
- Customize any package by adding extra pieces
- Pick up and decorate yourself, or add optional setup and takedown for a fee
- Seasonal collections highlighted on the home page
- Returning customers save 10% on every rental after their first one
- Reuse counter showing how many pieces were reused instead of thrown away
- My rentals page with status tracking, cancellation, and a packing checklist

**Admin**
- Dashboard with rentals waiting, pickups this week, items out, and late returns
- Move rentals through Requested, Confirmed, Picked up, Returned, or Cancelled
- Setup rentals show the event address and use "Set up" and "Taken down" steps
- Add, edit, hide, and show items

**Booking rules**
- Items are held from the day before the event to the day after, for pickup or setup
- No double booking: item rows are locked during booking (`SELECT ... FOR UPDATE`)
  so two people can't book the last unit at the same moment
- Events need at least 2 days notice and can be booked up to a year ahead

## Pricing

Prices were checked against Los Angeles rental companies in October 2026.
We stay below budget local shops (for example, a 120" round tablecloth is $12
here vs about $15 to $16 elsewhere), and our setup fee includes delivery,
setup, and takedown, while most competitors charge about $100 just to deliver.
Packages cost about 10 to 15% less than renting each piece on its own.

## Security

- Passwords stored with `password_hash()` (bcrypt)
- Every query uses PDO prepared statements (SQL injection protection)
- CSRF token on every form
- All output escaped with `htmlspecialchars()` (XSS protection)
- Session ID regenerated on login (session fixation protection)
- Login slows down after 5 wrong tries
- Same error for wrong email or wrong password (no account guessing)
- Customers can only see their own rentals, only admins can open `/admin`
- `config`, `includes`, and `database` folders are blocked from the web

## Folder structure

```
config/      settings and database login
database/    schema.sql (tables) and seed.sql (sample data)
includes/    shared code: database, login, availability logic, header, footer
public/      everything the browser can reach (this is the web root)
  admin/     admin pages
  assets/    CSS and JavaScript
```

## Live site

**https://zerowastedecor.website**

Want to try it? Log in as the sample customer `demo@zerowaste.test` with
password `ChangeMe123!`. The admin account on the live site uses a private password.

## Run it on your computer (Windows with XAMPP)

1. Install XAMPP from https://www.apachefriends.org and open the XAMPP Control Panel.
2. Click **Start** next to **MySQL**.
3. Click **Admin** next to MySQL to open phpMyAdmin.
4. Click the **Import** tab, choose `database/schema.sql`, and click **Import**.
5. Do the same with `database/seed.sql`.
6. Open PowerShell in the project folder and run:

```powershell
C:\xampp\php\php.exe -S localhost:8000 -t public
```

7. Go to http://localhost:8000 in your browser.

Keep PowerShell open while you use the site. Press Ctrl+C to stop it.

**Sample logins for local testing** (password for both is `ChangeMe123!`):
- Admin: `admin@zerowaste.test`
- Returning customer with past rentals: `demo@zerowaste.test`

Change the admin password before putting the site online.

## Settings

Business rules (setup fee for single items and the loyalty discount) are at the
top of `config/config.php`. Package setup fees are set per package in the database.

Defaults are in `config/config.php` and match a fresh XAMPP install
(user `root`, no password). On a real server, set these as environment
variables instead so no passwords go on GitHub:

| Variable    | What it is                     |
|-------------|--------------------------------|
| SITE_NAME   | Business name shown on the site |
| DB_HOST     | Database server address        |
| DB_PORT     | Usually 3306                   |
| DB_NAME     | decor_rental                   |
| DB_USER     | Database user                  |
| DB_PASS     | Database password              |
| APP_DEBUG   | `true` while building, `false` when live |

## How it is hosted

- **AWS Lightsail** server running Ubuntu 24.04, Apache, PHP 8.3, and MariaDB
- **Static IP** so the address never changes
- **Firewall** only allows web traffic (ports 80 and 443) and SSH
- **Custom domain** with DNS A records pointing to the server
- **HTTPS** with a free Let's Encrypt certificate that renews automatically
- **Separate database user** that can only read and write rows (it cannot drop tables or create users)
- Database login set with `SetEnv` in the Apache config, so no passwords are on GitHub
- `APP_DEBUG` set to `false` so error details are never shown to visitors
- Security updates installed with `apt upgrade`

Updating the live site: push to GitHub, then on the server run
`cd /var/www/DecorRentalSystem && sudo git pull`.

## Ideas for next features

- Admin page to create and edit packages and seasons
- Photo uploads for items
- Email confirmation when a rental is confirmed
- Late fee tracking
- Links to our Etsy and Amazon listings
- Load balancer with two web servers for higher traffic
