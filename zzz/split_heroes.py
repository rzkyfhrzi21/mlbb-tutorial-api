import json
import os
import re

# ====== KONFIGURASI PATH ======
INPUT_FILE = "heroes/heroes.json"
OUTPUT_DIR = "heroes"
# ==============================


def slugify(name: str) -> str:
    """
    Ubah nama hero menjadi nama file:
    'Rainy Day' -> 'rainy_day'
    """
    name = name.lower().strip()
    name = re.sub(r"\s+", "_", name)
    name = re.sub(r"[^\w_]", "", name)
    return name


def parse_number(value):
    """
    Konversi string angka ke int / float jika memungkinkan
    """
    if isinstance(value, (int, float)):
        return value

    if isinstance(value, str):
        if value.isdigit():
            return int(value)
        try:
            return float(value)
        except ValueError:
            return value

    return value


def main():
    # Pastikan folder output ada
    os.makedirs(OUTPUT_DIR, exist_ok=True)

    # Load file heroes.json
    with open(INPUT_FILE, "r", encoding="utf-8") as f:
        data = json.load(f)

    meta = data.get("meta", {})
    heroes = data.get("data", [])

    # Update meta title
    meta["title"] = "heroes_api"

    for hero in heroes:
        hero_name = hero.get("hero_name", "unknown")
        filename = f"{slugify(hero_name)}.json"
        filepath = os.path.join(OUTPUT_DIR, filename)

        # Normalisasi base_attributes (array -> object)
        base_attributes = hero.get("base_attributes", [])
        if isinstance(base_attributes, list) and base_attributes:
            hero["base_attributes"] = {
                k: parse_number(v)
                for k, v in base_attributes[0].items()
            }

        # Struktur final per hero
        output_data = {
            "meta": meta,
            "hero": hero
        }

        # Tulis file JSON
        with open(filepath, "w", encoding="utf-8") as f:
            json.dump(output_data, f, ensure_ascii=False, indent=2)

        print(f"✔ Generated: {filepath}")

    print("\n🎉 Semua hero berhasil di-split ke folder master/heroes/")


if __name__ == "__main__":
    main()
