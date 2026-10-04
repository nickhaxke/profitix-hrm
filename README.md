# Profitix HRM

Profitix HRM ni mfumo wa kisasa wa Usimamizi wa Rasilimali Watu (Human Resource Management) uliojengwa kwa **Laravel**. Mfumo huu una uwezo mkubwa wa kusimamia wafanyakazi, mahudhurio (Attendance), na umeunganishwa moja kwa moja (Integrated) na mashine za mahudhurio za **ZKTeco (ADMS)** kama vile SenseFace.

---

## ⚠️ Kwanini Folder la `vendor` halipo GitHub? Je, utafanya kazi?
Ndiyo, Mfumo utafanya kazi! Ni **Kawaida na Sahihi kabisa** kwa folder la `vendor` kutokuwepo kwenye GitHub. 
Kwenye mifumo yote ya Laravel (na PHP kwa ujumla), folder hili halipandishwi mtandaoni (lipo kwenye `.gitignore`) kwa sababu linabeba files nyingi sana za *Dependencies* (vifurushi).

**Jinsi inavyofanya kazi:**
Utakaposhusha (Clone) huu mfumo kwenye kompyuta mpya au Server nyingine, hutapata folder la `vendor`. Ili mfumo ufanye kazi, uta-run command moja tu:
```bash
composer install
```
Command hii itashusha upya mafaili yote ya `vendor` kulingana na faili lako la `composer.json` na mfumo utafanya kazi asilimia 100%. Hii inafanya GitHub yako iwe nyepesi na safi sana.

---

## 🚀 Jinsi ya Ku-Install na Kuendesha Mfumo (Local Setup)

Ili kuwasha huu mfumo kwenye kompyuta mpya, fuata hatua hizi kwa umakini:

### 1. Shusha Mfumo (Clone)
```bash
git clone https://github.com/nickhaxke/profitix-hrm.git
cd profitix-hrm
```

### 2. Install Dependencies (Kutengeneza folder la vendor)
```bash
composer install
npm install
npm run build
```

### 3. Copy Faili la Environment (.env)
Tengeneza faili la `.env` kwa kucopy kutoka `.env.example`:
```bash
cp .env.example .env
```
Kisha tengeneza Application Key:
```bash
php artisan key:generate
```

### 4. Database Setup
Ingia kwenye faili la `.env` na uweke jina la Database yako, Username, na Password. Baada ya hapo, weka Database (Migrations) kwa ku-run:
```bash
php artisan migrate --seed
```

### 5. Washa Mfumo
```bash
php artisan serve
```
Fungua browser yako na nenda: `http://localhost:8000`

---

## 🕒 Jinsi Mfumo wa ZKTeco (Mahudhurio) Unavyofanya Kazi

Mfumo huu umetengenezwa kuwa **Smart** kupokea data kutoka kwenye mashine za ZKTeco zenye ADMS bila kumtegemea mfanyakazi kubonyeza kitufe cha In/Out kwenye mashine.

* **URL ya Mashine (Server URL kwenye ZKTeco):** `http://domain-yako.com`
* **Server Port:** `80` au `443`
* **Jinsi Maamuzi ya In/Out Yanavyofanyika (Attendance Processor):**
  1. **Punch ya Kwanza ya Siku:** Inasomeka kama `CHECK-IN`.
  2. **Punches za Katikati ya Siku:** Zinawekwa `Ignored` (Ili kuzuia mtu kutoka kabla ya muda au kurudia rudia kuscan kwa makosa).
  3. **Punch Baada ya Muda wa Kutoka:** Inasomeka kama `CHECK-OUT`.
  4. **Muda Mkuu wa Kutoka (Earliest Checkout Time):** Unasomwa kutoka kwenye "Shift" aliyopewa mtu. Kama hana Shift, mfumo unatumia "Fallback Time" ambayo ni saa **10:00 Jioni (16:00)**.

---

## 📚 Makumbusho Muhimu (Cheatsheet)
* **Kusafisha Cache ikigoma:** `php artisan optimize:clear`
* **Kutengeneza Controller:** `php artisan make:controller JinaController`
* **Ku-Update Code kutoka GitHub (Kama upo kwenye Server / cPanel terminal):**
  ```bash
  git pull origin main
  composer install
  php artisan migrate
  ```

---
*Umetengenezwa na: Nick*
