import json
import os

# ===============================
# KONFIGURASI
# ===============================
TARGET_FOLDER = "heroes"  # folder tempat file hero json
ICON_FIELDS = ["hero_icon", "skill_icon"]

def normalize_icon(value: str) -> str:
    """
    - lowercase
    - hapus path assets/heroes/
    - paksa ekstensi .webp
    """
    if not isinstance(value, str):
        return value

    value = value.lower()

    # hapus path jika ada
    value = value.replace("/assets/heroes/", "")
    value = value.replace("assets/heroes/", "")

    # ambil nama file tanpa ekstensi
    name = os.path.splitext(value)[0]

    return f"{name}.webp"

def process_hero_file(filepath: str):
    with open(filepath, "r", encoding="utf-8") as f:
        data = json.load(f)

    hero = data.get("hero", {})

    # ===============================
    # HERO ICON
    # ===============================
    if "hero_icon" in hero:
        hero["hero_icon"] = normalize_icon(hero["hero_icon"])

    # ===============================
    # SKILL ICON
    # ===============================
    skills = hero.get("skills", [])
    for skill in skills:
        if "skill_icon" in skill:
            skill["skill_icon"] = normalize_icon(skill["skill_icon"])

    # ===============================
    # SIMPAN ULANG
    # ===============================
    with open(filepath, "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2, ensure_ascii=False)

    print(f"[OK] {os.path.basename(filepath)}")

def main():
    for file in os.listdir(TARGET_FOLDER):
        if file.endswith(".json"):
            process_hero_file(os.path.join(TARGET_FOLDER, file))

    print("\nSELESAI ✔ Semua icon telah dinormalisasi")

if __name__ == "__main__":
    main()
