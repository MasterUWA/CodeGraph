import torch
import torch.nn as nn
from torch_geometric.loader import DataLoader
from sklearn.metrics import f1_score, precision_score, recall_score
# Assuming you have a custom dataset class (we will need to build this next)
# from dataset import PHPGraphDataset 

def train_model():
    # 1. Hardware Configuration (Simulating commodity hardware)
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    
    # 2. Initialize the Model, Optimizer, and Loss Function
    model = LiteVulGNN(in_channels=64).to(device)
    optimizer = torch.optim.Adam(model.parameters(), lr=0.001)
    
    # Binary Cross Entropy Loss (standard for binary classification)
    criterion = nn.BCELoss() 
    
    # 3. Load the Dataset (Placeholder for your SARD / GitHub dataset)
    # dataset = PHPGraphDataset(root='data/')
    # train_loader = DataLoader(dataset, batch_size=32, shuffle=True)
    
    print("Starting Model Training...")
    model.train()
    
    # Simulated Epoch Loop
    epochs = 10
    for epoch in range(epochs):
        total_loss = 0
        
        # for batch in train_loader:
        #     batch = batch.to(device)
        #     optimizer.zero_grad()
        #     
        #     # Forward Pass
        #     predictions = model(batch.x, batch.edge_index, batch.batch)
        #     
        #     # Calculate Loss
        #     loss = criterion(predictions, batch.y.float().view(-1, 1))
        #     
        #     # Backpropagation
        #     loss.backward()
        #     optimizer.step()
        #     
        #     total_loss += loss.item()
            
        # print(f"Epoch {epoch+1}/{epochs} | Loss: {total_loss:.4f}")
        pass # Remove this pass when you plug in the real dataset

    # 4. Save the Model Weights (Proving it's under 12MB)
    torch.save(model.state_dict(), 'litevulgnn_weights.pth')
    print("Model weights saved to litevulgnn_weights.pth")

if __name__ == "__main__":
    train_model()