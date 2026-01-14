import os

# ===============================
# KONFIGURASI
# ===============================
TARGET_FOLDERS = [
    "battle_spells",
    "emblems",
    "heroes",
    "items",
    "skills"
]

EXTENSION = ".webp"

def rename_files(folder: str):
    if not os.path.isdir(folder):
        print(f"[SKIP] Folder tidak ditemukan: {folder}")
        return

    for filename in os.listdir(folder):
        if not filename.lower().endswith(EXTENSION):
            continue

        if " " not in filename:
            continue

        old_path = os.path.join(folder, filename)
        new_filename = filename.replace(" ", "_")
        new_path = os.path.join(folder, new_filename)

        if os.path.exists(new_path):
            print(f"[SKIP] Sudah ada: {new_filename}")
            continue

        os.rename(old_path, new_path)
        print(f"[RENAME] {folder}/{filename} -> {new_filename}")

def main():
    print("=== RENAME ICON WEBP (SPASI -> UNDERSCORE) ===\n")

    for folder in TARGET_FOLDERS:
        rename_files(folder)

    print("\nSELESAI ✔ Semua file telah dicek")

if __name__ == "__main__":
    main()
