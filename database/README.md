# database/

GUCAIR stores its data in MongoDB:

```text
Database:    gucair_database
Collections: universities
             assessments
```

This folder is for optional local scripts and backups. The website never loads these files automatically.

For local testing only, `sample_data.json` contains a few sample universities and assessments. Its passwords are already hashed with PHP `password_hash()`; see the root `README.md` for the sample login password and import command. Import with:

```bash
php database/import_sample_data.php
```

Do not put sample rows in application pages. Pages must read from MongoDB and show an honest empty state when a collection has no documents. Remove the sample file and imported records before final submission if the assignment requires it.
