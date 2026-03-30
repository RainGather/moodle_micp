#!/usr/bin/env python3
"""Generate 32-char proof codes (A-Z0-9) for lessons.

Usage:
  python3 generate_proof_codes.py --lessons 30

Outputs CSV to stdout: Lxx,BRONZE,<code> etc.
"""

from __future__ import annotations

import argparse
import secrets
import string

ALPHABET = string.ascii_uppercase + string.digits


def gen_code(n: int = 32) -> str:
    return "".join(secrets.choice(ALPHABET) for _ in range(n))


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--lessons", type=int, required=True)
    args = ap.parse_args()

    for i in range(1, args.lessons + 1):
        lesson = f"L{i:02d}"
        print(f"{lesson},BRONZE,{gen_code()}")
        print(f"{lesson},SILVER,{gen_code()}")
        print(f"{lesson},GOLD,{gen_code()}")


if __name__ == "__main__":
    main()
