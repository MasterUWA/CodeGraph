import torch
import json

def process_ast_to_graph(ast_json, feature_dim=64):
    """
    Parses the JSON AST from the PHP frontend and converts it into 
    PyTorch Geometric tensor formats: x (nodes) and edge_index (edges).
    """
    # If the input is a string, parse it to a dictionary
    if isinstance(ast_json, str):
        ast_data = json.loads(ast_json)
    else:
        ast_data = ast_json
        
    nodes = []
    edges = []
    
    # Recursive function to traverse the AST and build the graph
    def traverse(node, parent_id=None):
        if not isinstance(node, dict):
            return
            
        # Assign a unique integer ID to each node based on the current list length
        current_id = len(nodes)
        
        # Simulate generating a feature vector for the node (e.g., via Word2Vec)
        # For this implementation, we initialize a random tensor of size `feature_dim`
        node_feature = torch.rand(feature_dim)
        nodes.append(node_feature)
        
        # If this node has a parent, create a directed edge (Parent -> Child)
        if parent_id is not None:
            edges.append([parent_id, current_id])
            
        # Traverse child nodes (JSON arrays or nested dictionaries)
        for key, value in node.items():
            if isinstance(value, dict):
                traverse(value, current_id)
            elif isinstance(value, list):
                for item in value:
                    if isinstance(item, dict):
                        traverse(item, current_id)

    # Start the recursive traversal
    traverse(ast_data)
    
    # Fallback for empty scripts to prevent tensor errors
    if len(nodes) == 0:
        nodes.append(torch.rand(feature_dim))
        
    # Convert lists to PyTorch tensors
    x = torch.stack(nodes)
    
    if len(edges) > 0:
        # edge_index must be shape [2, num_edges] and type long
        edge_index = torch.tensor(edges, dtype=torch.long).t().contiguous()
    else:
        # Create an empty edge index if no edges exist
        edge_index = torch.empty((2, 0), dtype=torch.long)
        
    # Create a batch tensor (all nodes belong to graph 0)
    batch = torch.zeros(x.size(0), dtype=torch.long)
    
    return x, edge_index, batch