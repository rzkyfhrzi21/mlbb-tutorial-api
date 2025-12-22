import json
import os
import re

# ================== PATH ==================
INPUT_FILE = "heroes.json"          # file master (di folder zzz)
OUTPUT_DIR = "../heroes"            # output hero json
LIST_FILE = "../heroes/list_heroes.json"
# ==========================================


# ---------- UTIL ----------
def slugify(text: str) -> str:
    text = text.lower().strip()
    text = re.sub(r"\s+", "_", text)
    text = re.sub(r"[^\w_]", "", text)
    return text


def parse_number(val):
    if isinstance(val, (int, float)):
        return val
    if isinstance(val, str):
        if val.isdigit():
            return int(val)
        try:
            return float(val)
        except ValueError:
            return val
    return val


def load_master():
    with open(INPUT_FILE, "r", encoding="utf-8") as f:
        return json.load(f)


# =========================
# MENU 1 — SPLIT HERO
# =========================
def split_heroes():
    data = load_master()
    meta = data.get("meta", {})
    heroes = data.get("data", [])

    meta["title"] = "heroes_api"
    os.makedirs(OUTPUT_DIR, exist_ok=True)

    for hero in heroes:
        hero_name = hero.get("hero_name", "unknown")
        slug = slugify(hero_name)
        hero_json = f"{slug}.json"
        output_path = os.path.join(OUTPUT_DIR, hero_json)

        # ===== BUILD HERO OBJECT (URUTAN TERJAGA) =====
        hero_obj = {
            "hero_url": hero_json,
            "hero_icon": f"{slug}.webp",
            "hero_id": hero.get("hero_id"),
            "hero_name": hero_name,
            "release_year": hero.get("release_year"),
            "laning": hero.get("laning"),
            "role": hero.get("role"),
            "speciality": hero.get("speciality"),
            "price": hero.get("price"),
            "skills": [],
            "base_attributes": {}
        }

        # ===== Skills =====
        for skill in hero.get("skills", []):
            skill_name = skill.get("skill_name", "")
            skill_slug = re.sub(r"[^\w]", "_", skill_name).strip("_")

            hero_obj["skills"].append({
                "skill_name": skill_name,
                "skill_icon": f"{skill_slug}.webp",
                "type": skill.get("type"),
                "cooldown": skill.get("cooldown"),
                "manacost": skill.get("manacost"),
                "skill_unique": skill.get("skill_unique"),
                "description": skill.get("description")
            })

        # ===== Base Attributes =====
        base = hero.get("base_attributes", [])
        if isinstance(base, list) and base:
            hero_obj["base_attributes"] = {
                k: parse_number(v)
                for k, v in base[0].items()
            }

        # ===== Output =====
        with open(output_path, "w", encoding="utf-8") as f:
            json.dump(
                {
                    "meta": meta,
                    "hero": hero_obj
                },
                f,
                ensure_ascii=False,
                indent=2
            )

        print(f"✔ Generated hero: {hero_json}")

    print("\n🎉 Semua hero berhasil di-split!\n")


# =========================
# MENU 2 — LIST HEROES
# =========================
def generate_list():
    heroes_list = []
    os.makedirs(OUTPUT_DIR, exist_ok=True)

    for file in os.listdir(OUTPUT_DIR):
        if not file.endswith(".json") or file == "list_heroes.json":
            continue

        path = os.path.join(OUTPUT_DIR, file)

        if os.path.getsize(path) == 0:
            continue

        try:
            with open(path, "r", encoding="utf-8") as f:
                data = json.load(f)
        except json.JSONDecodeError:
            continue

        hero = data.get("hero")
        if not hero:
            continue

        heroes_list.append({
            "hero_name": hero.get("hero_name"),
            "hero_id": hero.get("hero_id"),
            "hero_icon": hero.get("hero_icon"),
            "hero_url": hero.get("hero_url")
        })

    heroes_list.sort(key=lambda x: x["hero_name"] or "")

    output = {
        "meta": {
            "title": "list_heroes_api",
            "patch_notes": "29-11-2025",
            "author": {
                "Adji Rivaldi": "https://www.instagram.com/rrival_dii/",
                "Dimas Sadewa": "https://www.instagram.com/d.dewaaaa/",
                "Rizky Fahrezi": "https://www.instagram.com/rzkydev666/"
            },
            "source": "https://github.com/rzkyfhrzi21/mlbb-tutorial-api",
            "description": "Data hero Mobile Legends: Bang Bang dengan skill bahasa Indonesia dan base_attributes terisi (angka mendekati Level 1, sebagian estimasi)."
        },
        "hero": heroes_list
    }

    with open(LIST_FILE, "w", encoding="utf-8") as f:
        json.dump(output, f, ensure_ascii=False, indent=2)

    print(f"\n✔ list_heroes.json dibuat ({len(heroes_list)} hero)\n")


# =========================
# MENU LOOP
# =========================
def show_menu():
    print("""
====================================
 MLBB HERO JSON GENERATOR
====================================
1. Split heroes.json → hero per file
2. Generate list_heroes.json
0. Keluar
====================================
""")


def main():
    while True:
        show_menu()
        choice = input("Pilih menu (0/1/2): ").strip()

        if choice == "1":
            split_heroes()
            input("Tekan ENTER untuk kembali ke menu...")
        elif choice == "2":
            generate_list()
            input("Tekan ENTER untuk kembali ke menu...")
        elif choice == "0":
            print("👋 Keluar.")
            break
        else:
            print("❌ Pilihan tidak valid.")
            input("Tekan ENTER...")


if __name__ == "__main__":
    main()
