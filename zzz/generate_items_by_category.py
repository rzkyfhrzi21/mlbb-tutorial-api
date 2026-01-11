import json
import os
import re

# ================== PATH ==================
INPUT_FILE = "../items/items.json"          # master item file
OUTPUT_DIR = "../items"            # output folder
# ==========================================


# ---------- UTIL ----------
def slugify(text: str) -> str:
    text = text.lower().strip()
    text = re.sub(r"\s+", "_", text)
    text = re.sub(r"[^\w_]", "", text)
    return text


def load_master():
    with open(INPUT_FILE, "r", encoding="utf-8") as f:
        return json.load(f)


# =========================
# SPLIT ITEM BY CATEGORY
# =========================
def split_items_by_category():
    data = load_master()
    meta = data.get("meta", {})
    items = data.get("data", [])

    os.makedirs(OUTPUT_DIR, exist_ok=True)

    category_map = {}

    # ===== GROUPING =====
    for item in items:
        categories = item.get("category", [])
        if not isinstance(categories, list):
            continue

        for cat in categories:
            cat_key = slugify(cat)

            if cat_key not in category_map:
                category_map[cat_key] = []

            category_map[cat_key].append(item)

    # ===== WRITE FILE PER CATEGORY =====
    for cat, item_list in category_map.items():
        output_path = os.path.join(OUTPUT_DIR, f"{cat}.json")

        output = {
            "meta": {
                "title": f"items_{cat}_api",
                "patch_notes": meta.get("patch_notes"),
                "author": meta.get("author"),
                "source": meta.get("source"),
                "description": f"Data item Mobile Legends: Bang Bang kategori {cat.capitalize()}."
            },
            "category": cat.capitalize(),
            "total": len(item_list),
            "data": item_list
        }

        with open(output_path, "w", encoding="utf-8") as f:
            json.dump(output, f, ensure_ascii=False, indent=2)

        print(f"✔ Generated {cat}.json ({len(item_list)} items)")

    print("\n🎉 Semua item berhasil dipisahkan per kategori!\n")


# =========================
# MENU
# =========================
def show_menu():
    print("""
====================================
 MLBB ITEM JSON GENERATOR
====================================
1. Split items.json → per category
0. Keluar
====================================
""")


def main():
    while True:
        show_menu()
        choice = input("Pilih menu (0/1): ").strip()

        if choice == "1":
            split_items_by_category()
            input("Tekan ENTER untuk kembali ke menu...")
        elif choice == "0":
            print("👋 Keluar.")
            break
        else:
            print("❌ Pilihan tidak valid.")
            input("Tekan ENTER...")


if __name__ == "__main__":
    main()
