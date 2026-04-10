# Money Calculator (HTML + JS + CSS + Database)

A tiny web app to record **income/expense** transactions in **BDT** (Bangladeshi Taka), with **separate income and expense lists**, and summary cards for **total income, total expense, and balance**.

**Two people:** **Sajib hossain** (person `1`) and **Razib hossain** (person `2`) each have their **own password**. After login, each person sees only **their** income list, expense list, and totals (data is tagged with `person: 1` or `2` in IndexedDB). Hashes are stored in **localStorage**; the current person is in **sessionStorage** until log out. This is **browser-only** security (not a substitute for a real server login). Existing installs migrate old “income/expense” passwords to person 1 / person 2 and assign old rows to person 1.

**Reset passwords** asks for an **authentication phone number** (stored in `app.js` as `RESET_AUTH_PHONE`, default **01731362650**). If it matches, saved password hashes are cleared and you set new passwords. Transaction data in IndexedDB is **not** removed. Accepted formats include `01731362650`, `+8801731362650`, and `8801731362650`. To use a different number, change `RESET_AUTH_PHONE` in `app.js` (and the placeholder in `index.html` if you like).

It uses **IndexedDB** (a built-in browser database). Data is stored locally in your browser for this site.

Use **http://127.0.0.1** (or HTTPS) so **Web Crypto** works; `file://` may block some features.

## Opening link (how to open the app)

**Easiest (Mac / Linux):** in Terminal:

```bash
cd "/Users/ashikurrahman/untitled folder 7"
./serve.sh
```

It prints a link. Open **http://127.0.0.1:8766** in Chrome, Safari, or Firefox (default port **8766** avoids “port already in use” on **5173**).

Or manually:

```bash
cd "/path/to/untitled folder 7"
python3 -m http.server 8766
```

Then open **http://127.0.0.1:8766** (or **http://127.0.0.1:8766/index.html**).

### If it “failed to open”

| Problem | What to do |
|--------|------------|
| **Address already in use** | Another app is using that port. Use another port: `./serve.sh 9999` then open `http://127.0.0.1:9999`. |
| **Wrong folder** | Run `ls` — you should see `index.html`, `app.js`, `styles.css`. The folder name has a **space**; always `cd` with quotes: `cd "untitled folder 7"`. |
| **Blank page or errors** | Use `http://127.0.0.1:...` **not** `https://` unless you set up TLS. |
| **Python not found** | Install Python 3 or run `python -m http.server 8766` instead of `python3`. |
| **Only `file://` works badly** | Don’t rely on double‑clicking `index.html`; use the local server above so IndexedDB works. |

## Run locally

`./serve.sh` or `python3 -m http.server 8766` → **http://127.0.0.1:8766**

## Host on GitHub (and GitHub Pages)

1. **Create a new repository** on [GitHub](https://github.com/new) (empty repo, no need to add a README if you already have one here).
2. In this folder, add the GitHub remote and push (replace `YOUR_USER` and `YOUR_REPO`):

   ```bash
   cd "/Users/ashikurrahman/untitled folder 7"
   git remote add origin https://github.com/YOUR_USER/YOUR_REPO.git
   git push -u origin main
   ```

   If you are copying the project without git history, run `git init`, `git branch -M main`, `git add .`, and `git commit -m "Initial commit"` first, then add `origin` and push.

3. **Turn on GitHub Pages:** in the repo on GitHub → **Settings** → **Pages** → **Build and deployment** → Source: **Deploy from a branch** → Branch: **main** → Folder: **/ (root)** → Save.
4. After a minute, the site is at **`https://YOUR_USER.github.io/YOUR_REPO/`** (exact URL is shown on the Pages settings page).

Assets use relative paths (`./styles.css`, `./app.js`), so they work on Pages. **HTTPS** on `github.io` is fine for **Web Crypto** and IndexedDB.

**Security note:** Anything in the repo (including `RESET_AUTH_PHONE` in `app.js`) is public if the repo is public. Use a private repo if you want the code off the open web, or change/remove the reset phone before pushing.

## Data

- Database: IndexedDB `money_calc_db`
- Object store: `transactions`

