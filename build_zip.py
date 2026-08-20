#!/usr/bin/env python3
"""Bangun ZIP siap-upload cPanel untuk aplikasi Kehadiran Pemkab Sinjai.

Hanya menyertakan file yang dibutuhkan produksi PHP. Sengaja MENGECUALIKAN:
  - node_modules/, server.js, package*.json  -> khusus development Node.js
  - *.html                                    -> versi lama tanpa perbaikan ModSecurity
  - database/kehadiran.db                     -> dibuat otomatis di server (kredensial bersih)
  - database/db.js                            -> versi Node.js dari db.php
  - admin.zip, build_zip.py                   -> artefak build
"""

import os
import zipfile

ROOT = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(ROOT, 'kehadiran-cpanel.zip')

# File di root yang wajib disertakan.
ROOT_FILES = [
    '.htaccess',
    '.htaccess-1-barebones',
    '.htaccess-2-routing',
    'api.php',
    'index.php',
    'login.php',
    'admin.php',
    'meeting.php',
    'CPANEL_DEPLOYMENT_GUIDE.md',
    'CPANEL_TROUBLESHOOT.md',
    # Fallback logo saat assets/ gagal dimuat (dipakai onerror di meeting.php).
    'sinjai.png',
    'kominfo.png',
]

# File dalam subfolder, ditulis dengan path relatif apa adanya.
SUB_FILES = [
    'database/.htaccess',
    'database/db.php',
    'database/agencies_cache.json',
    'assets/sinjai.png',
    'assets/sinjai2.png',
    'assets/kominfo.png',
    'assets/sinjai.svg',
    'assets/kominfo.svg',
]


def main():
    if os.path.exists(OUT):
        os.remove(OUT)

    added, missing = [], []

    with zipfile.ZipFile(OUT, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for rel in ROOT_FILES + SUB_FILES:
            src = os.path.join(ROOT, rel.replace('/', os.sep))
            if os.path.isfile(src):
                z.write(src, rel)
                added.append((rel, os.path.getsize(src)))
            else:
                missing.append(rel)

    print(f'ZIP dibuat: {OUT}')
    print(f'Ukuran    : {os.path.getsize(OUT) / 1024:.1f} KB')
    print(f'Isi       : {len(added)} file\n')

    for rel, size in added:
        print(f'  {size / 1024:9.1f} KB  {rel}')

    if missing:
        print('\nTIDAK DITEMUKAN (dilewati):')
        for rel in missing:
            print(f'  - {rel}')


if __name__ == '__main__':
    main()
