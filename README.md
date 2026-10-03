# DecorRentalSystem

A full stack event decoration rental website built with PHP, MySQL, HTML, and CSS.
Customers pick their event date, see what decor is free that day, and request a rental.
Pickup is the day before the event and return is the day after.

Built as a prototype for my ENT 310 business project at CSUN and as practice for my
CIT 480 senior project. The store name "Encore Decor" is a placeholder.

## Features

**Customers**
- Sign up and log in
- Browse decor by category, search, and event date with live availability
- Ready made packages for quinceañeras, weddings, birthdays, and Fourth of July
- Request a rental for a single item or a full package
- My rentals page with status tracking and cancellation
- Packing checklist for returning items

**Admin**
- Dashboard with rentals waiting, pickups this week, items out, and late returns
- Move rentals through Requested, Confirmed, Picked up, Returned, or Cancelled
- Add, edit, hide, and show items

**Booking rules**
- Items are held from the day before the event to the day after
- No double booking: item rows are locked during booking (`SELECT ... FOR UPDATE`)
  so two people can't book the last unit at the same moment
- Events need at least 2 days notice and can be booked up to a year ahead

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

**Admin login:** `admin@encoredecor.test` / `ChangeMe123!`
Change this before putting the site online.

## Settings

Defaults are in `config/config.php` and match a fresh XAMPP install
(user `root`, no password). On a real server, set these as environment
variables instead so no passwords go on GitHub:

| Variable    | What it is                     |
|-------------|--------------------------------|
| SITE_NAME   | Store name shown on the site   |
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

- Admin page to create and edit packages
- Photo uploads for items
- Email confirmation when a rental is confirmed
- Late fee tracking
