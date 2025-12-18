import json
import os

# ===== KONFIGURASI =====
BASE_DIR = os.path.dirname(os.path.abspath(__file__))   # .../project/zzz
HEROES_DIR = os.path.join(BASE_DIR, "..", "heroes")     # .../project/heroes
OUTPUT_FILE = os.path.join(HEROES_DIR, "list_heroes.json")
# ======================


def main():
    heroes_list = []

    for filename in sorted(os.listdir(HEROES_DIR)):
        if not filename.endswith(".json") or filename == "list_heroes.json":
            continue

        filepath = os.path.join(HEROES_DIR, filename)

        with open(filepath, "r", encoding="utf-8") as f:
            data = json.load(f)

        hero = data.get("hero", {})

        heroes_list.append({
            "hero_icon": hero.get("hero_icon"),
            "hero_id": hero.get("hero_id"),
            "hero_name": hero.get("hero_name")
        })

    output_data = {
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

    with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
        json.dump(output_data, f, ensure_ascii=False, indent=2)

    print(f"✔ Generated: {OUTPUT_FILE}")
    print(f"✔ Total hero: {len(heroes_list)}")


if __name__ == "__main__":
    main()
