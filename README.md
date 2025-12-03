# CSC4110 Project 2 - Home Cleaning Service

Web-based system for managing home-cleaning services for Anna Johnson.

## Setup

1. Clone the repo into your web root (XAMPP htdocs):

   ```bash
   git clone https://github.com/Anushka113/CSC4110-project-2.git

2. Create MySQL database cleaning_db and import schema.sql.

3. Update db.php with your MySQL username/password.

4. Start Apache and MySQL in XAMPP.

5. Open http://localhost/CSC4110-project-2/index.php in your browser.


💾 Save.

---

## 6️⃣ Test on localhost

1. Start **XAMPP** → click **Start** on **Apache** and **MySQL**
2. Open browser → go to:

   👉 `http://localhost/CSC4110-project-2/index.php`

You should see:

- Title: *Anna Johnson Home Cleaning Service*
- “PHP is running ✔”
- “Database connection successful ✔”

If you get an error, copy-paste it to me and I’ll fix it.

---

## 7️⃣ Commit + Push to GitHub

Open **Git Bash** in the project folder:

```bash
cd /c/xampp/htdocs/CSC4110-project-2

git status
git add .
git commit -m "Add initial schema, db connection, and homepage"
git push
