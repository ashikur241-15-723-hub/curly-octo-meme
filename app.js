/* IndexedDB + two people: each has their own income list, expense list, and totals. Password per person (client-side). */

const DB_NAME = "money_calc_db";
const DB_VERSION = 2;
const STORE = "transactions";

const LS_PERSON1_HASH = "money_auth_person1_hash";
const LS_PERSON2_HASH = "money_auth_person2_hash";
const LS_INCOME_HASH_LEGACY = "money_auth_income_hash";
const LS_EXPENSE_HASH_LEGACY = "money_auth_expense_hash";
const SESSION_PERSON = "money_auth_person";

/** Normalized BD mobile for password-reset gate (starts with 0). */
const RESET_AUTH_PHONE = "01731362650";

const PERSON_1_DISPLAY_NAME = "Sajib hossain";
const PERSON_2_DISPLAY_NAME = "Razib hossain";

/** @typedef {1|2} PersonId */
/** @typedef {{id?: number, person: PersonId, createdAt: string, kind: "income"|"expense", amountCents: number, description: string, category: string}} Transaction */

const $ = (id) => /** @type {HTMLElement} */ (document.getElementById(id));

const els = {
  navHomeBtn: /** @type {HTMLButtonElement} */ ($("navHomeBtn")),
  authGate: $("authGate"),
  appShell: $("appShell"),
  authSetup: $("authSetup"),
  authLogin: $("authLogin"),
  setupForm: /** @type {HTMLFormElement} */ ($("setupForm")),
  setupIncomePw: /** @type {HTMLInputElement} */ ($("setupIncomePw")),
  setupIncomePw2: /** @type {HTMLInputElement} */ ($("setupIncomePw2")),
  setupExpensePw: /** @type {HTMLInputElement} */ ($("setupExpensePw")),
  setupExpensePw2: /** @type {HTMLInputElement} */ ($("setupExpensePw2")),
  setupMsg: $("setupMsg"),
  loginIncomePw: /** @type {HTMLInputElement} */ ($("loginIncomePw")),
  loginExpensePw: /** @type {HTMLInputElement} */ ($("loginExpensePw")),
  loginIncomeBtn: $("loginIncomeBtn"),
  loginExpenseBtn: $("loginExpenseBtn"),
  loginIncomeMsg: $("loginIncomeMsg"),
  loginExpenseMsg: $("loginExpenseMsg"),
  firstRunBanner: $("firstRunBanner"),
  goSetupBtn: $("goSetupBtn"),
  backToLoginFromSetupBtn: $("backToLoginFromSetupBtn"),
  authLoginFooter: $("authLoginFooter"),
  authResetPwBtn: $("authResetPwBtn"),
  resetPwBackdrop: $("resetPwBackdrop"),
  resetPwCloseBtn: $("resetPwCloseBtn"),
  resetPwCancelBtn: $("resetPwCancelBtn"),
  resetAuthPhone: /** @type {HTMLInputElement} */ ($("resetAuthPhone")),
  resetPwVerifyBtn: /** @type {HTMLButtonElement} */ ($("resetPwVerifyBtn")),
  resetPwMsg: $("resetPwMsg"),
  logoutBtn: $("logoutBtn"),
  roleBadge: $("roleBadge"),

  totalIncome: $("totalIncome"),
  totalExpense: $("totalExpense"),
  balance: $("balance"),
  txnForm: $("txnForm"),
  kind: /** @type {HTMLSelectElement} */ ($("kind")),
  amount: /** @type {HTMLInputElement} */ ($("amount")),
  description: /** @type {HTMLInputElement} */ ($("description")),
  category: /** @type {HTMLInputElement} */ ($("category")),
  resetBtn: $("resetBtn"),
  clearAllBtn: $("clearAllBtn"),
  formMsg: $("formMsg"),

  searchIncome: /** @type {HTMLInputElement} */ ($("searchIncome")),
  searchExpense: /** @type {HTMLInputElement} */ ($("searchExpense")),
  incomeTbody: $("incomeTbody"),
  expenseTbody: $("expenseTbody"),
  emptyIncome: $("emptyIncome"),
  emptyExpense: $("emptyExpense"),
  incomeCount: $("incomeCount"),
  expenseCount: $("expenseCount"),
  printIncomeBtn: $("printIncomeBtn"),
  printExpenseBtn: $("printExpenseBtn"),
  printSurface: $("printSurface"),

  exportBtn: $("exportBtn"),
  importInput: /** @type {HTMLInputElement} */ ($("importInput")),

  modalBackdrop: $("modalBackdrop"),
  closeModalBtn: $("closeModalBtn"),
  cancelEditBtn: $("cancelEditBtn"),
  editForm: $("editForm"),
  editId: /** @type {HTMLInputElement} */ ($("editId")),
  editKind: /** @type {HTMLSelectElement} */ ($("editKind")),
  editAmount: /** @type {HTMLInputElement} */ ($("editAmount")),
  editDescription: /** @type {HTMLInputElement} */ ($("editDescription")),
  editCategory: /** @type {HTMLInputElement} */ ($("editCategory")),
  editMsg: $("editMsg"),
};

function migrateLegacyAuthHashes() {
  if (localStorage.getItem(LS_PERSON1_HASH) && localStorage.getItem(LS_PERSON2_HASH)) return;
  const o1 = localStorage.getItem(LS_INCOME_HASH_LEGACY);
  const o2 = localStorage.getItem(LS_EXPENSE_HASH_LEGACY);
  if (o1 && o2) {
    localStorage.setItem(LS_PERSON1_HASH, o1);
    localStorage.setItem(LS_PERSON2_HASH, o2);
  }
}

/** @returns {PersonId | null} */
function getSessionPerson() {
  const s = sessionStorage.getItem(SESSION_PERSON);
  if (s === "1" || s === "2") return /** @type {PersonId} */ (Number(s));
  return null;
}

/** @param {PersonId} person */
function setSessionPerson(person) {
  sessionStorage.setItem(SESSION_PERSON, String(person));
}

function clearSession() {
  sessionStorage.removeItem(SESSION_PERSON);
}

/** @param {PersonId} id */
function personDisplayName(id) {
  return id === 1 ? PERSON_1_DISPLAY_NAME : PERSON_2_DISPLAY_NAME;
}

function passwordsConfigured() {
  migrateLegacyAuthHashes();
  return !!(localStorage.getItem(LS_PERSON1_HASH) && localStorage.getItem(LS_PERSON2_HASH));
}

function clearStoredPasswordHashes() {
  localStorage.removeItem(LS_PERSON1_HASH);
  localStorage.removeItem(LS_PERSON2_HASH);
  localStorage.removeItem(LS_INCOME_HASH_LEGACY);
  localStorage.removeItem(LS_EXPENSE_HASH_LEGACY);
}

function normalizePhoneForReset(raw) {
  let s = String(raw ?? "").replace(/[\s-]/g, "");
  if (s.startsWith("+880")) s = "0" + s.slice(4);
  else if (s.startsWith("880") && s.length >= 12) s = "0" + s.slice(3);
  return s;
}

function phoneMatchesResetAuth(raw) {
  return normalizePhoneForReset(raw) === RESET_AUTH_PHONE;
}

function verifyPhoneAndCompletePasswordReset() {
  if (!phoneMatchesResetAuth(els.resetAuthPhone.value)) {
    throw new Error("Wrong number. Enter the authentication phone number.");
  }
  clearStoredPasswordHashes();
  clearSession();
  closeResetPwModal();
  hideApp();
  showAuthSetup();
  setMsg(
    els.setupMsg,
    `Passwords cleared. Set new passwords for ${PERSON_1_DISPLAY_NAME} and ${PERSON_2_DISPLAY_NAME}.`
  );
}

async function sha256Hex(text) {
  const enc = new TextEncoder().encode(text);
  const buf = await crypto.subtle.digest("SHA-256", enc);
  return [...new Uint8Array(buf)].map((b) => b.toString(16).padStart(2, "0")).join("");
}

/** @param {PersonId} person */
async function verifyPassword(person, password) {
  const key = person === 1 ? LS_PERSON1_HASH : LS_PERSON2_HASH;
  const stored = localStorage.getItem(key);
  if (!stored) return false;
  const h = await sha256Hex(password);
  return h === stored;
}

function showAuthSetup() {
  els.authSetup.classList.remove("hidden");
  els.authLogin.classList.add("hidden");
}

function updateFirstRunBanner() {
  const has = passwordsConfigured();
  if (els.firstRunBanner) els.firstRunBanner.classList.toggle("hidden", has);
  if (els.authLoginFooter) els.authLoginFooter.classList.toggle("hidden", !has);
}

function showAuthLogin() {
  els.authSetup.classList.add("hidden");
  els.authLogin.classList.remove("hidden");
  updateFirstRunBanner();
}

function showApp() {
  els.authGate.classList.add("hidden");
  els.appShell.classList.remove("hidden");
}

function hideApp() {
  els.appShell.classList.add("hidden");
  els.authGate.classList.remove("hidden");
}

function openResetPwModal() {
  setMsg(els.resetPwMsg, "");
  els.resetAuthPhone.value = "";
  els.resetPwBackdrop.classList.remove("hidden");
  els.resetAuthPhone.focus();
}

function closeResetPwModal() {
  els.resetPwBackdrop.classList.add("hidden");
  setMsg(els.resetPwMsg, "");
  els.resetAuthPhone.value = "";
}

/** Close overlays; login screen is "home" when not in the app; otherwise scroll dashboard to top. */
function goHome() {
  if (!els.resetPwBackdrop.classList.contains("hidden")) closeResetPwModal();
  if (!els.modalBackdrop.classList.contains("hidden")) closeModal();

  const inApp = getSessionPerson() && !els.appShell.classList.contains("hidden");
  if (inApp) {
    window.scrollTo({ top: 0, behavior: "smooth" });
    return;
  }

  showAuthLogin();
  setMsg(els.setupMsg, "");
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function applyPersonUI() {
  const p = getSessionPerson();
  if (!p) return;
  els.roleBadge.textContent = `Logged in as ${personDisplayName(p)} · `;
  els.kind.disabled = false;
  els.editKind.disabled = false;
}

/** @param {Transaction[]} all */
/** @param {PersonId} person */
function txnsForPerson(all, person) {
  return all.filter((t) => Number(t.person) === person);
}

/** @param {PersonId} person */
async function deleteAllForPerson(person) {
  const all = await getAllTxns();
  for (const t of all) {
    if (Number(t.person) === person) await deleteTxn(t.id);
  }
}

function fmtMoneyBDT(cents) {
  const val = cents / 100;
  try {
    return new Intl.NumberFormat("en-BD", {
      style: "currency",
      currency: "BDT",
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(val);
  } catch {
    return `BDT ${val.toFixed(2)}`;
  }
}

function setMsg(el, msg, kind = "info") {
  el.textContent = msg || "";
  el.dataset.kind = kind;
}

function nowIso() {
  return new Date().toISOString();
}

function parseMoneyToCents(raw) {
  let s = String(raw ?? "")
    .trim()
    .replaceAll(",", "")
    .replace(/^BDT\s*/i, "")
    .replace(/^[Tk\u09F3\u09f3]+\s*/i, "")
    .trim();
  if (!s) throw new Error("Amount is required.");
  if (!/^\d+(\.\d{1,2})?$/.test(s)) throw new Error("Use a BDT amount like 1500 or 50.50");
  const [a, b = ""] = s.split(".");
  const cents = Number(a) * 100 + Number((b + "00").slice(0, 2));
  if (!Number.isFinite(cents) || cents < 0) throw new Error("Amount must be a positive number.");
  return cents;
}

function openDb() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(DB_NAME, DB_VERSION);
    req.onupgradeneeded = (event) => {
      const db = event.target.result;
      const tx = event.target.transaction;
      const oldV = event.oldVersion;

      if (!db.objectStoreNames.contains(STORE)) {
        const store = db.createObjectStore(STORE, { keyPath: "id", autoIncrement: true });
        store.createIndex("createdAt", "createdAt", { unique: false });
        store.createIndex("kind", "kind", { unique: false });
        store.createIndex("person", "person", { unique: false });
        return;
      }

      const store = tx.objectStore(STORE);
      if (oldV < 2) {
        if (!store.indexNames.contains("person")) {
          store.createIndex("person", "person", { unique: false });
        }
        store.openCursor().onsuccess = (e) => {
          const cursor = e.target.result;
          if (cursor) {
            const v = /** @type {Transaction} */ (cursor.value);
            if (v.person == null) {
              v.person = 1;
              cursor.update(v);
            }
            cursor.continue();
          }
        };
      }
    };
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error || new Error("Failed to open DB"));
  });
}

async function withStore(mode, fn) {
  const db = await openDb();
  try {
    return await new Promise((resolve, reject) => {
      const tx = db.transaction(STORE, mode);
      const store = tx.objectStore(STORE);
      let out;
      try {
        out = fn(store, resolve, reject);
      } catch (e) {
        reject(e);
      }
      tx.oncomplete = () => resolve(out);
      tx.onerror = () => reject(tx.error || new Error("DB transaction error"));
      tx.onabort = () => reject(tx.error || new Error("DB transaction aborted"));
    });
  } finally {
    db.close();
  }
}

function reqToPromise(req) {
  return new Promise((resolve, reject) => {
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error || new Error("DB request failed"));
  });
}

async function addTxn(txn) {
  await withStore("readwrite", (store) => store.add(txn));
}

async function putTxn(txn) {
  await withStore("readwrite", (store) => store.put(txn));
}

async function deleteTxn(id) {
  await withStore("readwrite", (store) => store.delete(id));
}

async function getAllTxns() {
  return await withStore("readonly", (store) => reqToPromise(store.getAll()));
}

async function getTxnById(id) {
  return await withStore("readonly", (store) => reqToPromise(store.get(id)));
}

function normalizeText(s) {
  return String(s ?? "").trim();
}

function escHtml(s) {
  return String(s ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;");
}

function sortTxnsNewestFirst(rows) {
  return [...rows].sort((a, b) =>
    b.createdAt > a.createdAt ? 1 : b.createdAt < a.createdAt ? -1 : (b.id || 0) - (a.id || 0)
  );
}

/** @param {"income"|"expense"} kind */
async function printKindList(kind) {
  const p = getSessionPerson();
  if (!p) return;

  const all = txnsForPerson(await getAllTxns(), p);
  const rows = sortTxnsNewestFirst(all.filter((t) => t.kind === kind));
  const title =
    kind === "income"
      ? `Income list — ${personDisplayName(p)} (BDT)`
      : `Expense list — ${personDisplayName(p)} (BDT)`;
  let sum = 0;
  for (const t of rows) sum += t.amountCents;

  const rowHtml = rows
    .map((t) => {
      const d = escHtml(dateLabel(t.createdAt));
      const amt = escHtml(fmtMoneyBDT(t.amountCents));
      const desc = escHtml(t.description);
      const cat = escHtml(t.category || "");
      return `<tr><td>${d}</td><td class="num">${amt}</td><td>${desc}</td><td>${cat}</td></tr>`;
    })
    .join("");

  const emptyRow = `<tr><td colspan="4" class="print-empty">No ${kind} entries yet.</td></tr>`;

  const printedAt = new Date().toLocaleString("en-BD");
  const tfoot =
    rows.length > 0
      ? `<tfoot><tr><td><strong>Total</strong></td><td class="num"><strong>${escHtml(fmtMoneyBDT(sum))}</strong></td><td colspan="2"></td></tr></tfoot>`
      : "";

  const inner = `
  <h1>${escHtml(title)}</h1>
  <p class="meta">Printed: ${escHtml(printedAt)} · ${rows.length} row(s)</p>
  <table>
    <thead><tr><th>Date</th><th class="num">Amount (BDT)</th><th>Description</th><th>Category</th></tr></thead>
    <tbody>${rows.length ? rowHtml : emptyRow}</tbody>
    ${tfoot}
  </table>`;

  const surface = els.printSurface;
  surface.innerHTML = inner;
  surface.removeAttribute("aria-hidden");

  const cleanup = () => {
    surface.innerHTML = "";
    surface.setAttribute("aria-hidden", "true");
  };
  window.addEventListener("afterprint", cleanup, { once: true });

  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      try {
        window.print();
      } catch (e) {
        cleanup();
        throw e;
      }
    });
  });
}

function renderTotals(all) {
  let income = 0;
  let expense = 0;
  for (const t of all) {
    if (t.kind === "income") income += t.amountCents;
    else expense += t.amountCents;
  }
  const bal = income - expense;
  els.totalIncome.textContent = fmtMoneyBDT(income);
  els.totalExpense.textContent = fmtMoneyBDT(expense);
  els.balance.textContent = fmtMoneyBDT(bal);
  els.balance.classList.toggle("income", bal >= 0);
  els.balance.classList.toggle("expense", bal < 0);
}

function matchesSearch(t, q) {
  if (!q) return true;
  const hay = `${t.description || ""} ${t.category || ""}`.toLowerCase();
  return hay.includes(q.toLowerCase());
}

function dateLabel(iso) {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  return d.toLocaleString("en-BD", {
    year: "numeric",
    month: "short",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function makeRow(t) {
  const tr = document.createElement("tr");

  const tdDate = document.createElement("td");
  tdDate.textContent = dateLabel(t.createdAt);

  const tdAmount = document.createElement("td");
  tdAmount.className = "right";
  tdAmount.textContent = fmtMoneyBDT(t.amountCents);
  tdAmount.style.color = t.kind === "income" ? "rgba(34,197,94,0.95)" : "rgba(249,115,22,0.95)";

  const tdDesc = document.createElement("td");
  tdDesc.textContent = t.description;

  const tdCat = document.createElement("td");
  tdCat.textContent = t.category || "";
  tdCat.style.color = t.category ? "rgba(255,255,255,0.82)" : "rgba(255,255,255,0.45)";

  const tdActions = document.createElement("td");
  tdActions.className = "right";
  const wrap = document.createElement("div");
  wrap.className = "row-actions";

  const editBtn = document.createElement("button");
  editBtn.type = "button";
  editBtn.className = "icon-btn";
  editBtn.textContent = "Edit";
  editBtn.addEventListener("click", () => openEdit(t.id));

  const delBtn = document.createElement("button");
  delBtn.type = "button";
  delBtn.className = "icon-btn";
  delBtn.textContent = "Delete";
  delBtn.addEventListener("click", async () => {
    if (!confirm("Delete this transaction?")) return;
    await deleteTxn(t.id);
    await refresh();
  });

  wrap.appendChild(editBtn);
  wrap.appendChild(delBtn);
  tdActions.appendChild(wrap);

  tr.appendChild(tdDate);
  tr.appendChild(tdAmount);
  tr.appendChild(tdDesc);
  tr.appendChild(tdCat);
  tr.appendChild(tdActions);

  return tr;
}

function openModal() {
  els.modalBackdrop.classList.remove("hidden");
}
function closeModal() {
  els.modalBackdrop.classList.add("hidden");
  setMsg(els.editMsg, "");
}

async function openEdit(id) {
  const p = getSessionPerson();
  const t = await getTxnById(Number(id));
  if (!t || !p) return;
  if (Number(t.person) !== p) return;

  els.editId.value = String(t.id);
  els.editKind.value = t.kind;
  els.editAmount.value = (t.amountCents / 100).toFixed(2);
  els.editDescription.value = t.description;
  els.editCategory.value = t.category || "";
  openModal();
  els.editAmount.focus();
  els.editAmount.select();
}

async function refresh() {
  const p = getSessionPerson();
  if (!p) return;

  const allRaw = await getAllTxns();
  const all = txnsForPerson(allRaw, p);
  all.sort((a, b) => (b.createdAt > a.createdAt ? 1 : b.createdAt < a.createdAt ? -1 : (b.id || 0) - (a.id || 0)));

  renderTotals(all);

  const qIn = normalizeText(els.searchIncome.value);
  const qEx = normalizeText(els.searchExpense.value);

  const incomes = all.filter((t) => t.kind === "income").filter((t) => matchesSearch(t, qIn));
  const expenses = all.filter((t) => t.kind === "expense").filter((t) => matchesSearch(t, qEx));

  els.incomeTbody.innerHTML = "";
  for (const t of incomes) els.incomeTbody.appendChild(makeRow(t));

  els.expenseTbody.innerHTML = "";
  for (const t of expenses) els.expenseTbody.appendChild(makeRow(t));

  els.incomeCount.textContent = incomes.length ? `${incomes.length}` : "";
  els.expenseCount.textContent = expenses.length ? `${expenses.length}` : "";

  els.emptyIncome.style.display = incomes.length === 0 ? "block" : "none";
  els.emptyExpense.style.display = expenses.length === 0 ? "block" : "none";
}

async function exportJson() {
  const p = getSessionPerson();
  if (!p) return;
  const all = txnsForPerson(await getAllTxns(), p);
  const payload = {
    exportedAt: nowIso(),
    version: 2,
    person: p,
    displayName: personDisplayName(p),
    transactions: all,
  };
  const blob = new Blob([JSON.stringify(payload, null, 2)], { type: "application/json" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  const slug = personDisplayName(p)
    .toLowerCase()
    .replace(/\s+/g, "-")
    .replace(/[^a-z0-9-]/g, "");
  a.download = `money-${slug || `person${p}`}-${new Date().toISOString().slice(0, 10)}.json`;
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
}

async function importJsonFile(file) {
  const p = getSessionPerson();
  if (!p) return 0;

  const text = await file.text();
  let data;
  try {
    data = JSON.parse(text);
  } catch {
    throw new Error("Invalid JSON file.");
  }
  const txns = Array.isArray(data?.transactions) ? data.transactions : Array.isArray(data) ? data : null;
  if (!txns) throw new Error("JSON must contain a `transactions` array (or be an array).");

  const existing = await getAllTxns();
  const existingIds = new Set(existing.map((t) => t.id));

  let added = 0;
  for (const raw of txns) {
    const kind = raw?.kind === "income" || raw?.kind === "expense" ? raw.kind : null;
    const createdAt = typeof raw?.createdAt === "string" ? raw.createdAt : nowIso();
    const description = normalizeText(raw?.description);
    const category = normalizeText(raw?.category);
    const amountCents = Number(raw?.amountCents);
    if (!kind || !description || !Number.isInteger(amountCents) || amountCents < 0) continue;

    /** @type {Transaction} */
    const t = { person: p, createdAt, kind, amountCents, description, category };

    if (Number.isInteger(raw?.id) && !existingIds.has(raw.id)) {
      t.id = raw.id;
    }
    await addTxn(t);
    added++;
  }
  return added;
}

function wireAuth() {
  els.setupForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    setMsg(els.setupMsg, "");
    const a = els.setupIncomePw.value;
    const a2 = els.setupIncomePw2.value;
    const b = els.setupExpensePw.value;
    const b2 = els.setupExpensePw2.value;
    if (a !== a2) {
      setMsg(els.setupMsg, `${PERSON_1_DISPLAY_NAME}'s passwords do not match.`);
      return;
    }
    if (b !== b2) {
      setMsg(els.setupMsg, `${PERSON_2_DISPLAY_NAME}'s passwords do not match.`);
      return;
    }
    if (a.length < 4 || b.length < 4) {
      setMsg(els.setupMsg, "Each password must be at least 4 characters.");
      return;
    }
    try {
      const h1 = await sha256Hex(a);
      const h2 = await sha256Hex(b);
      localStorage.setItem(LS_PERSON1_HASH, h1);
      localStorage.setItem(LS_PERSON2_HASH, h2);
      els.setupForm.reset();
      setMsg(els.setupMsg, `Saved. Log in as ${PERSON_1_DISPLAY_NAME} or ${PERSON_2_DISPLAY_NAME}.`);
      showAuthLogin();
    } catch (err) {
      setMsg(els.setupMsg, err?.message || "Could not save passwords.");
    }
  });

  els.goSetupBtn.addEventListener("click", () => {
    setMsg(els.loginIncomeMsg, "");
    setMsg(els.loginExpenseMsg, "");
    showAuthSetup();
  });

  els.backToLoginFromSetupBtn.addEventListener("click", () => {
    setMsg(els.setupMsg, "");
    showAuthLogin();
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  els.loginIncomeBtn.addEventListener("click", async () => {
    setMsg(els.loginIncomeMsg, "");
    if (!passwordsConfigured()) {
      setMsg(els.loginIncomeMsg, "Set up passwords first (use the button above).");
      return;
    }
    const pw = els.loginIncomePw.value;
    if (!pw) {
      setMsg(els.loginIncomeMsg, "Enter your password.");
      return;
    }
    const ok = await verifyPassword(1, pw);
    if (!ok) {
      setMsg(els.loginIncomeMsg, "Wrong password.");
      return;
    }
    setSessionPerson(1);
    els.loginIncomePw.value = "";
    enterApp();
  });

  els.loginIncomePw.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      els.loginIncomeBtn.click();
    }
  });

  els.loginExpensePw.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      els.loginExpenseBtn.click();
    }
  });

  els.loginExpenseBtn.addEventListener("click", async () => {
    setMsg(els.loginExpenseMsg, "");
    if (!passwordsConfigured()) {
      setMsg(els.loginExpenseMsg, "Set up passwords first (use the button above).");
      return;
    }
    const pw = els.loginExpensePw.value;
    if (!pw) {
      setMsg(els.loginExpenseMsg, "Enter your password.");
      return;
    }
    const ok = await verifyPassword(2, pw);
    if (!ok) {
      setMsg(els.loginExpenseMsg, "Wrong password.");
      return;
    }
    setSessionPerson(2);
    els.loginExpensePw.value = "";
    enterApp();
  });

  els.logoutBtn.addEventListener("click", () => {
    clearSession();
    els.roleBadge.textContent = "";
    hideApp();
    showAuthLogin();
    setMsg(els.loginIncomeMsg, "");
    setMsg(els.loginExpenseMsg, "");
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  els.authResetPwBtn.addEventListener("click", () => {
    if (!passwordsConfigured()) return;
    openResetPwModal();
  });

  els.resetPwCloseBtn.addEventListener("click", () => closeResetPwModal());
  els.resetPwCancelBtn.addEventListener("click", () => closeResetPwModal());
  els.resetPwBackdrop.addEventListener("click", (e) => {
    if (e.target === els.resetPwBackdrop) closeResetPwModal();
  });

  els.resetPwVerifyBtn.addEventListener("click", () => {
    setMsg(els.resetPwMsg, "");
    els.resetPwVerifyBtn.disabled = true;
    try {
      verifyPhoneAndCompletePasswordReset();
    } catch (err) {
      setMsg(els.resetPwMsg, err?.message || "Verification failed.");
    } finally {
      els.resetPwVerifyBtn.disabled = false;
    }
  });

  els.resetAuthPhone.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      els.resetPwVerifyBtn.click();
    }
  });
}

async function enterApp() {
  showApp();
  applyPersonUI();
  try {
    await openDb();
    await refresh();
  } catch (e) {
    setMsg(els.formMsg, e?.message || "IndexedDB unavailable in this browser/context.");
  }
}

function wireEvents() {
  els.txnForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    setMsg(els.formMsg, "");
    try {
      const p = getSessionPerson();
      if (!p) return;
      const kind = els.kind.value === "income" ? "income" : "expense";
      const amountCents = parseMoneyToCents(els.amount.value);
      const description = normalizeText(els.description.value);
      const category = normalizeText(els.category.value);
      if (!description) throw new Error("Description is required.");

      /** @type {Transaction} */
      const t = { person: p, createdAt: nowIso(), kind, amountCents, description, category };
      await addTxn(t);
      els.amount.value = "";
      els.description.value = "";
      els.category.value = "";
      els.amount.focus();
      setMsg(els.formMsg, "Saved.");
      await refresh();
    } catch (err) {
      setMsg(els.formMsg, err?.message || "Failed to save.");
    }
  });

  els.resetBtn.addEventListener("click", () => {
    els.amount.value = "";
    els.description.value = "";
    els.category.value = "";
    els.amount.focus();
    setMsg(els.formMsg, "");
  });

  els.clearAllBtn.addEventListener("click", async () => {
    const p = getSessionPerson();
    if (!p) return;
    if (
      !confirm(
        `Delete ALL transactions for ${personDisplayName(p)} (income and expenses)? This cannot be undone.`
      )
    )
      return;
    await deleteAllForPerson(p);
    await refresh();
  });

  els.searchIncome.addEventListener("input", () => refresh());
  els.searchExpense.addEventListener("input", () => refresh());

  els.printIncomeBtn.addEventListener("click", async () => {
    try {
      await printKindList("income");
    } catch (e) {
      setMsg(els.formMsg, e?.message || "Could not print income list.");
    }
  });
  els.printExpenseBtn.addEventListener("click", async () => {
    try {
      await printKindList("expense");
    } catch (e) {
      setMsg(els.formMsg, e?.message || "Could not print expense list.");
    }
  });

  els.exportBtn.addEventListener("click", () => exportJson());

  els.importInput.addEventListener("change", async () => {
    const file = els.importInput.files?.[0];
    els.importInput.value = "";
    if (!file) return;
    try {
      const added = await importJsonFile(file);
      const who = getSessionPerson();
      if (who) setMsg(els.formMsg, `Imported ${added} transaction(s) for ${personDisplayName(who)}.`);
      await refresh();
    } catch (e) {
      setMsg(els.formMsg, e?.message || "Import failed.");
    }
  });

  els.closeModalBtn.addEventListener("click", () => closeModal());
  els.cancelEditBtn.addEventListener("click", () => closeModal());
  els.modalBackdrop.addEventListener("click", (e) => {
    if (e.target === els.modalBackdrop) closeModal();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      if (!els.modalBackdrop.classList.contains("hidden")) closeModal();
      if (!els.resetPwBackdrop.classList.contains("hidden")) closeResetPwModal();
    }
  });

  els.editForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    setMsg(els.editMsg, "");
    try {
      const p = getSessionPerson();
      const id = Number(els.editId.value);
      if (!Number.isInteger(id) || !p) throw new Error("Missing transaction id.");
      const existing = await getTxnById(id);
      if (!existing) throw new Error("Transaction not found.");
      if (Number(existing.person) !== p) throw new Error("You cannot edit this entry.");

      const kind = els.editKind.value === "income" ? "income" : "expense";
      const amountCents = parseMoneyToCents(els.editAmount.value);
      const description = normalizeText(els.editDescription.value);
      const category = normalizeText(els.editCategory.value);
      if (!description) throw new Error("Description is required.");

      /** @type {Transaction} */
      const updated = {
        id,
        person: p,
        createdAt: existing.createdAt,
        kind,
        amountCents,
        description,
        category,
      };
      await putTxn(updated);
      setMsg(els.editMsg, "Saved changes.");
      await refresh();
      closeModal();
    } catch (err) {
      setMsg(els.editMsg, err?.message || "Failed to save changes.");
    }
  });
}

function bootAuthScreen() {
  migrateLegacyAuthHashes();
  if (getSessionPerson()) {
    enterApp();
    return;
  }
  showAuthLogin();
}

function main() {
  els.navHomeBtn.addEventListener("click", () => goHome());
  wireAuth();
  wireEvents();
  bootAuthScreen();
}

main();
