# graph_builder.py
"""Converts the token-graph JSON produced by extract_ast.php into PyTorch Geometric tensors."""
import torch


def collect_token_vocab(ast_jsons):
    """Scans a list of token-graph JSON dicts and builds a stable token-type vocabulary."""
    vocab = set()
    for ast_json in ast_jsons:
        if not ast_json or "nodes" not in ast_json:
            continue
        for node in ast_json["nodes"]:
            vocab.add(node["type"])
    return {token_type: idx for idx, token_type in enumerate(sorted(vocab))}


def process_ast_to_graph(ast_json, vocab):
    """Converts a single token-graph JSON dict into (x, edge_index, node_types) tensors.

    x is a one-hot encoding of each node's token type over the shared `vocab`,
    so every graph in the dataset ends up with the same feature width.
    """
    nodes = ast_json.get("nodes", [])
    edges = ast_json.get("edges", [])

    if not nodes:
        return None, None, None

    num_features = len(vocab)
    x = torch.zeros((len(nodes), num_features), dtype=torch.float)
    node_types = []
    for node in nodes:
        node_types.append(node["type"])
        feature_idx = vocab.get(node["type"])
        if feature_idx is not None:
            x[node["id"], feature_idx] = 1.0

    if edges:
        # Add both directions so message passing can flow either way along the token graph.
        src = [e[0] for e in edges] + [e[1] for e in edges]
        dst = [e[1] for e in edges] + [e[0] for e in edges]
        edge_index = torch.tensor([src, dst], dtype=torch.long)
    else:
        edge_index = torch.empty((2, 0), dtype=torch.long)

    return x, edge_index, node_types
