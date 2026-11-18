# Deployment

Local XAMPP setup is documented in `README.md`. This guide covers a production-style deployment using:

- **Docker** with `php:8.3-apache`
- **MongoDB Atlas** free cluster
- **Render** Web Service

## Deployment files added

| File | Purpose |
|---|---|
| `Dockerfile` | Installs PHP 8.3, Apache, the MongoDB PHP extension, and Composer dependencies. |
| `docker-start.sh` | Reconfigures Apache to listen on Render's `$PORT`. |
| `.dockerignore` | Keeps local vendor, Git, and editor files out of the image. |
| `.gitignore` | Keeps dependencies and secrets out of Git. |
| `config/database.php` | Reads `MONGODB_URI`; falls back to `mongodb://127.0.0.1:27017`. |

Internal links are relative, so the app works at `http://localhost/gucair/` in XAMPP and at the domain root on Render.

---

## 1. MongoDB Atlas free cluster

1. Create a free Atlas account at `https://www.mongodb.com/atlas`.
2. Create a project, then choose **Build a Database**.
3. Select the **M0 Free** cluster and choose a provider/region close to your Render region.
4. Under **Database Access**, choose **Add New Database User**:
   - Authentication: **Password**
   - Save the username and password.
   - Role: **Read and write to any database** (or `readWrite` on `gucair_database`).
5. Under **Network Access**, choose **Add IP Address**:
   - For Render, use **Allow Access from Anywhere** (`0.0.0.0/0`).
   - This is common for Render because its outbound IPs can change. Restrict it later if your plan provides stable outbound IPs.
6. Under **Connect**, choose **Connect your application**:
   - Driver: **PHP**
   - Copy a connection string like:

   ```text
   mongodb+srv://USERNAME:PASSWORD@cluster0.xxxxx.mongodb.net/?retryWrites=true&w=majority
   ```

7. Replace `USERNAME` and `PASSWORD` with your database user. URL-encode special characters in the password:

   | Character | Encoded |
   |---|---|
   | `@` | `%40` |
   | `:` | `%3A` |
   | `#` | `%23` |
   | `/` | `%2F` |
   | `%` | `%25` |
   | `!` | `%21` |

   The app selects `gucair_database` itself, so you do not need to add it to the URI.

Never commit the finished connection string to Git. It will be stored only in Render's environment variables.

---

## 2. Optional local Docker test

Build and run the image:

```powershell
docker build -t gucair .
docker run --rm -p 8080:80 -e MONGODB_URI="mongodb+srv://USERNAME:PASSWORD@cluster0.xxxxx.mongodb.net/?retryWrites=true&w=majority" gucair
```

Open:

```text
http://localhost:8080/
```

If MongoDB is running on the Windows host instead of Atlas, use:

```powershell
docker run --rm -p 8080:80 -e MONGODB_URI="mongodb://host.docker.internal:27017" gucair
```

---

## 3. Deploy to Render

1. Push the project to GitHub or GitLab. Make sure `composer.lock`, `Dockerfile`, and `docker-start.sh` are committed.
2. In Render, choose **New +** → **Web Service**.
3. Connect the repository.
4. Use these settings:
   - **Runtime:** Docker (Render detects the `Dockerfile`)
   - **Branch:** `main` (or your chosen branch)
   - **Plan:** Free is fine for a lab demo
5. Under **Environment**, add:

   ```text
   Key:   MONGODB_URI
   Value: mongodb+srv://USERNAME:PASSWORD@cluster0.xxxxx.mongodb.net/?retryWrites=true&w=majority
   ```

6. Optionally set **Health Check Path** to `/`.
7. Click **Create Web Service**. Render will build the Docker image, install the MongoDB extension and Composer packages, then start Apache on the port Render provides.
8. When the deploy finishes, open:

   ```text
   https://YOUR-SERVICE-NAME.onrender.com/
   ```

`docker-start.sh` handles Render's `PORT` variable, so no custom start command is needed.

---

## 4. Optional sample data on a deployed instance

The sample file is not loaded automatically. If you want to try it locally against Atlas, set the URI first:

```powershell
$env:MONGODB_URI = "mongodb+srv://USERNAME:PASSWORD@cluster0.xxxxx.mongodb.net/?retryWrites=true&w=majority"
php database\import_sample_data.php
```

Sample logins and the shared sample password are documented in `README.md`. Remove the sample records before final submission if your assignment requires an empty database.

---

## 5. Troubleshooting

| Problem | Likely cause | Fix |
|---|---|---|
| `Database setup incomplete` | `vendor/` is missing | Confirm Docker build ran `composer install` and `composer.lock` is committed. |
| `MongoDB could not be reached` | Bad URI, credentials, or network access | Check `MONGODB_URI`, Atlas Database Access, and Network Access. URL-encode the password. |
| Deployed links show 404 | Not using the Dockerfile, or custom routing was added | Deploy as a Docker Web Service and keep the relative links unchanged. |
| First request is slow | Free Render instances and Atlas clusters pause when idle | Wait a few seconds; upgrade the plan if this is unacceptable. |
| Apache starts but port is wrong | `docker-start.sh` was not included | Confirm the file is committed and the Dockerfile copies it. |
