# build_dataset.py
"""Builds the PHP vulnerability graph dataset: tokenizes PHP files via extract_ast.php,
converts each token-graph into PyG tensors via graph_builder, and saves the resulting
list of Data objects to disk.
"""
import os
import sys
import glob
import json
import subprocess

import torch
from torch_geometric.data import Data

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from graph_builder import collect_token_vocab, process_ast_to_graph

_THIS_DIR = os.path.dirname(os.path.abspath(__file__))
ROOT_DIR = os.path.dirname(_THIS_DIR)  # DataSet/ (parent of raw/)
EXTRACT_SCRIPT = os.path.join(_THIS_DIR, 'extract_ast.php')


def extract_php_ast(php_file_path):
    """Executes extract_ast.php and retrieves the JSON token-graph."""
    try:
        result = subprocess.run(
            ['php', EXTRACT_SCRIPT, php_file_path],
            capture_output=True,
            text=True,
            timeout=10
        )
        return json.loads(result.stdout)
    except Exception:
        return None


def build_dataset_from_raw():
    categories = [
        (os.path.join(ROOT_DIR, 'vulnerable'), 1),  # Target y = 1 (Vulnerable)
        (os.path.join(ROOT_DIR, 'secure'), 0)       # Target y = 0 (Secure)
    ]

    print("Beginning Dataset Extraction...")

    # Pass 1: extract every AST first so a shared token-type vocabulary can be built.
    file_asts = []
    for folder_path, label in categories:
        php_files = glob.glob(os.path.join(folder_path, '*.php'))
        print(f"Processing {len(php_files)} files in {folder_path}...")

        for file_path in php_files:
            ast_json = extract_php_ast(file_path)

            # Skip unparseable files
            if not ast_json or (isinstance(ast_json, dict) and "error" in ast_json):
                continue

            file_asts.append((ast_json, label))

    if not file_asts:
        print("No parseable PHP files found. Aborting.")
        return

    # Pass 2: build a shared vocabulary so every graph has the same feature width.
    vocab = collect_token_vocab([ast for ast, _ in file_asts])

    data_list = []
    for ast_json, label in file_asts:
        # Convert JSON token-graph into PyG tensor representations
        x, edge_index, _ = process_ast_to_graph(ast_json, vocab)
        if x is None:
            continue

        # Define target binary label tensor y
        y = torch.tensor([label], dtype=torch.float)

        # Create a PyTorch Geometric Data object
        graph_data = Data(x=x, edge_index=edge_index, y=y)
        data_list.append(graph_data)

    print(f"Processing Complete! Successfully generated {len(data_list)} graph objects.")

    # Save the processed graph list and vocabulary to disk
    processed_dir = os.path.join(ROOT_DIR, 'processed')
    os.makedirs(processed_dir, exist_ok=True)
    torch.save(data_list, os.path.join(processed_dir, 'php_dataset.pt'))
    with open(os.path.join(processed_dir, 'vocab.json'), 'w') as f:
        json.dump(vocab, f, indent=2)
    print(f"Dataset saved to {os.path.join(processed_dir, 'php_dataset.pt')}")


if __name__ == "__main__":
    build_dataset_from_raw()