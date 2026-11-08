import os
import sys
import zipfile

def build_zip():
    script_dir = os.path.dirname(os.path.abspath(__file__))
    source_dir = script_dir
    output_zip = os.path.join(source_dir, "wc-smart-checkout-builder.zip")
    plugin_slug = "wc-smart-checkout-builder"

    print(f"Building {output_zip} with forward slashes (/) ...")

    # Files to include at root of plugin folder
    root_files = [
        "wc-smart-checkout-builder.php",
        "update.json",
        "README.md"
    ]

    # Directories to include recursively
    include_dirs = [
        "assets",
        "includes",
        "templates"
    ]

    # Remove existing zip if present
    if os.path.exists(output_zip):
        os.remove(output_zip)

    count = 0
    with zipfile.ZipFile(output_zip, "w", zipfile.ZIP_DEFLATED) as z:
        # Add root files
        for filename in root_files:
            file_path = os.path.join(source_dir, filename)
            if os.path.isfile(file_path):
                arcname = f"{plugin_slug}/{filename}".replace("\\", "/")
                z.write(file_path, arcname)
                count += 1
                print(f"Added: {arcname}")

        # Add directories
        for dirname in include_dirs:
            dir_path = os.path.join(source_dir, dirname)
            if os.path.isdir(dir_path):
                for root, _, files in os.walk(dir_path):
                    for file in files:
                        full_path = os.path.join(root, file)
                        rel_path = os.path.relpath(full_path, source_dir).replace("\\", "/")
                        arcname = f"{plugin_slug}/{rel_path}".replace("\\", "/")
                        z.write(full_path, arcname)
                        count += 1

    print(f"Successfully packaged {count} entries into {output_zip}!")

if __name__ == "__main__":
    build_zip()
