import torch
import torch.nn.functional as F
from torch.nn import Linear
from torch_geometric.nn import GatedGraphConv, global_mean_pool, global_max_pool

class LiteVulGNN(torch.nn.Module):
    def __init__(self, in_channels, hidden_channels=64, num_layers=3):
        """
        LiteVulGNN: Edge-deployable Graph Neural Network for SQLi Detection
        - in_channels: Dimension of initial node features (e.g., Word2Vec size)
        - hidden_channels: Internal dimension size (default 64 per Chapter 4)
        - num_layers: Number of message-passing iterations (K=3)
        """
        super(LiteVulGNN, self).__init__()
        
        # Phase 4: Initial embedding to project input features to hidden dimensions
        self.embedding = Linear(in_channels, hidden_channels)
        
        # Phase 5: Message Passing Loop
        # GatedGraphConv inherently uses a GRU for node state updates,
        # satisfying the lightweight, parameter-sharing requirement.
        self.ggnn = GatedGraphConv(out_channels=hidden_channels, num_layers=num_layers)
        
        # Phase 7: Final classification layer 
        # Input is hidden_channels * 2 because we concatenate Mean and Max pooling
        self.classifier = Linear(hidden_channels * 2, 1)

    def forward(self, x, edge_index, batch):
        """
        x: Node feature matrix [num_nodes, in_channels]
        edge_index: Graph connectivity matrix [2, num_edges]
        batch: Indicates which graph in the batch each node belongs to
        """
        # 1. Initialization
        x = self.embedding(x)
        x = F.relu(x)
        
        # 2. Spatial Aggregation & GRU State Update
        x = self.ggnn(x, edge_index)
        
        # 3. Global Readout (Concatenating Mean and Max Pooling)
        x_mean = global_mean_pool(x, batch)
        x_max = global_max_pool(x, batch)
        x_global = torch.cat([x_mean, x_max], dim=1)
        
        # 4. Binary Classification (Secure vs. Vulnerable)
        out = self.classifier(x_global)
        return torch.sigmoid(out)

# --- Quick Sanity Check for your Supervisor ---
if __name__ == "__main__":
    # Simulate a graph with 10 nodes, 64-dim features
    dummy_x = torch.randn(10, 64) 
    # Simulate 12 edges (source, target)
    dummy_edge_index = torch.randint(0, 10, (2, 12)) 
    # All nodes belong to graph '0'
    dummy_batch = torch.zeros(10, dtype=torch.long) 
    
    model = LiteVulGNN(in_channels=64)
    output = model(dummy_x, dummy_edge_index, dummy_batch)
    
    print(f"Model Engine Successfully Initialized!")
    print(f"Total Trainable Parameters: {sum(p.numel() for p in model.parameters())}")
    print(f"Output Probability (0 to 1): {output.item():.4f}")