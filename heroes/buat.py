import json
import os
import argparse

def to_list(value):
    if value is None:
        return []
    if isinstance(value, list):
        return value
    if isinstance(value, str):
        return [value.strip()] if value.strip() else []
    return [value]

def load_json(path):
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)

def save_json(path, data):
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--list", required=True)
    parser.add_argument("--folder", default=".")
    parser.add_argument("--out", default="")
    parser.add_argument("--default-laning", default="")
    parser.add_argument("--default-role", default="")
    args = parser.parse_args()

    default_laning = [x.strip() for x in args.default_laning.split(",") if x.strip()]
    default_role   = [x.strip() for x in args.default_role.split(",") if x.strip()]

    list_data = load_json(args.list)
    out_path = args.out if args.out else args.list

    updated = 0
    not_found = []

    for h in list_data["hero"]:
        hero_file = h.get("hero_url")
        if not hero_file:
            h["laning"] = default_laning
            h["role"] = default_role
            continue

        path = os.path.join(args.folder, hero_file)

        if not os.path.exists(path):
            h["laning"] = default_laning
            h["role"] = default_role
            not_found.append(hero_file)
            continue

        hero_json = load_json(path)

        # 🔴 FIX UTAMA ADA DI SINI
        hero_data = hero_json.get("hero", {})

        laning = to_list(hero_data.get("laning"))
        role   = to_list(hero_data.get("role"))

        if not laning:
            laning = default_laning
        if not role:
            role = default_role

        h["laning"] = laning
        h["role"] = role
        updated += 1

    save_json(out_path, list_data)

    print("SELESAI")
    print(f"Hero terupdate : {updated}/{len(list_data['hero'])}")
    if not_found:
        print("File hero tidak ditemukan:")
        for x in not_found:
            print("-", x)

if __name__ == "__main__":
    main()
