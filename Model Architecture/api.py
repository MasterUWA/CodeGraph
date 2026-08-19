from flask import Flask, request, jsonify
import torch
from model import LiteVulGNN # Your model architecture script
from graph_builder import process_ast_to_graph # The script we just created

app = Flask(__name__)

# Initialize the model and load the trained weights
# (Ensure 'litevulgnn_weights.pth' exists from your Jupyter training)
print("Loading LiteVulGNN Engine into memory...")
model = LiteVulGNN(in_channels=64)
try:
    model.load_state_dict(torch.load('litevulgnn_weights.pth', weights_only=True))
    print("Weights loaded successfully.")
except FileNotFoundError:
    print("Warning: Weights file not found. Using untrained initialization for testing.")
    
model.eval() # Lock the model for inference only

@app.route('/scan', methods=['POST'])
def scan_code():
    try:
        # 1. Receive the JSON payload from the PHP web application
        payload = request.get_json()
        
        if not payload:
            return jsonify({"status": "error", "message": "No JSON payload received"}), 400
            
        # 2. Convert the payload into PyG tensors
        x, edge_index, batch = process_ast_to_graph(payload)
        
        # 3. Execute the forward pass through LiteVulGNN
        with torch.no_grad():
            output = model(x, edge_index, batch)
            probability = output.item()
            
        # 4. Format and return the vulnerability assessment
        return jsonify({
            "status": "success",
            "vulnerability_probability": round(probability, 4),
            "is_vulnerable": probability > 0.5, # 50% threshold
            "nodes_analyzed": x.size(0)
        })
        
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500

if __name__ == '__main__':
    # Run the server on port 5000
    app.run(host='0.0.0.0', port=5000)