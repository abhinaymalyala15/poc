# Deploy in 5 steps (Render + Twilio)

Do these in order in your browser. This repo already includes `render.yaml` at the root.

---

## Step 1 — Deploy with Blueprint

1. Open [Render Dashboard](https://dashboard.render.com).
2. **New → Blueprint**.
3. Connect **GitHub** and select repo **`abhinaymalyala15/poc`** (or your fork).
4. Confirm Render detects **`render.yaml`** → **Apply** / create resources.

---

## Step 2 — Wait for deploy

- Wait **2–5 minutes** until the **web service** shows **Live**.
- Copy your URL, e.g. `https://poc-web.onrender.com` (name may differ).

---

## Step 3 — Environment variables

Open **Web Service → Environment** and add (replace values with yours):

```env
APP_KEY=base64:PASTE_FROM_ARTISAN
APP_URL=https://YOUR-SERVICE.onrender.com

TWILIO_SID=ACxxxxxxxx
TWILIO_AUTH_TOKEN=xxxxxxxx
OPENAI_API_KEY=sk-xxxxxxxx
SARVAM_API_KEY=sk_xxxxxxxx
```

**Generate `APP_KEY` on your PC** (run once in the project folder):

```bash
php artisan key:generate --show
```

Copy the full `base64:...` line into Render.

**Admin login (Call logs)** — registration is off in production. Add:

```env
ADMIN_EMAIL=you@example.com
ADMIN_PASSWORD=choose-a-strong-password
```

Then **Render Shell** on the web service:

```bash
php artisan db:seed --force
```

**Save** env vars → **Manual Deploy → Clear build cache & deploy** (or restart) so Laravel reloads config.

---

## Step 4 — Twilio webhook

1. [Twilio Console](https://console.twilio.com) → **Phone Numbers** → your number.
2. **Voice & Fax** → **A call comes in** → **Webhook**.
3. **HTTP POST** →  
   `https://YOUR-SERVICE.onrender.com/incoming-call`
4. **Save**.

---

## Step 5 — Test call

Dial your Twilio number from a phone. You should hear the greeting, record a message, then hear the AI reply.

**If something fails:** check **Render → Logs** and **Twilio → Monitor → Calls → Debugger** for errors.

**Later (security):** set `TWILIO_VALIDATE_SIGNATURE=true` in Render after calls work reliably.
