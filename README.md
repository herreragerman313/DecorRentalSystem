# DecorRentalSystem

Website for **Zero Waste Event Design Services**, our ENT 310 business at CSUN
(Zayli Tellez, Neria Arinda, Joshua Mineros, and German Herrera). Built with PHP,
MySQL, HTML, and CSS, and also used as practice for my CIT 480 senior project.

The business replaces throwaway party decorations with stylish, reusable ones.
We rent and set up reusable decor, and we also sell some pieces.

## Features

**Customers**
- Sign up and log in
- Browse decor by category, search, and event date with live availability
- Themed packages for quinceañeras, weddings, birthdays, and holidays
- Customize any package by adding extra pieces
- Choose pickup, or have our team set it up and take it down (setup fee)
- Seasonal collections highlighted on the home page
- Buy some pieces to keep from the Shop page
- Returning customers save 10% on every rental after their first one
- Reuse counter showing how many pieces were reused instead of thrown away
- My rentals page with status tracking, cancellation, orders, and a packing checklist

**Admin**
- Dashboard with rentals waiting, pickups this week, items out, and late returns
- Move rentals through Requested, Confirmed, Picked up, Returned, or Cancelled
- Setup rentals show the event address and use "Set up" and "Taken down" steps
- Shop orders page: mark orders ready, picked up, or cancel (stock goes back)
- Add, edit, hide, and show items, including sale price and stock

**Booking rules**
- Items are held from the day before the event to the day after, for pickup or setup
- Rental stock and sale stock are tracked separately, so selling a piece never breaks a booking
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

## Run it on your computer (Windows with XAMPP)

1. Install XAMPP from https://www.apachefriends.org and open the XAMPP Control Panel.
2. Click **Start** next to **MySQL**.
3. Click **Admin** next to MySQL to open phpMyAdmin.
4. Click the **Import** tab, choose `database/schema.sql`, and click **Import**.
5. Do the same with `database/seed.sql`.
6. Open PowerShell and run:

```powershell
cd C:\Users\herre\DecorRentalSystem
C:\xampp\php\php.exe -S localhost:8000 -t public
```

7. Go to http://localhost:8000 in your browser.

Keep PowerShell open while you use the site. Press Ctrl+C to stop it.

**Logins** (password for both is `ChangeMe123!`):
- Admin: `admin@zerowaste.test`
- Returning customer with past rentals: `demo@zerowaste.test`

Change these before putting the site online.

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

## Deploying to AWS (plan)

1. Two Ubuntu EC2 instances running Apache and PHP, with the web root set to `public/`.
2. MySQL primary server, plus a replica for backups and reads.
3. Application Load Balancer in front of both web servers.
4. Set the database settings with `SetEnv` in the Apache site config.
5. Turn on **sticky sessions** on the load balancer target group. PHP saves logins
   on each server's disk, so without this a user could get logged out when the
   load balancer sends them to the other server.
6. Set `APP_DEBUG` to `false` and change the admin password.

## Ideas for next features

- Admin page to create and edit packages and seasons
- Photo uploads for items
- Email confirmation when a rental is confirmed
- Late fee tracking
- Links to our Etsy and Amazon listings
