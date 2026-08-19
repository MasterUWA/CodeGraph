# dataset.py
import torch
from torch_geometric.data import InMemoryDataset

class PHPGraphDataset(InMemoryDataset):
    def __init__(self, root, transform=None, pre_transform=None):
        # root must point at the DataSet/ folder, which contains processed/php_dataset.pt
        super(PHPGraphDataset, self).__init__(root, transform, pre_transform)
        # Load the pre-built graph list directly into memory (built by build_dataset.py)
        self.data_list = torch.load(self.processed_paths[0], weights_only=False)

    @property
    def raw_file_names(self):
        return []

    @property
    def processed_file_names(self):
        return ['php_dataset.pt']

    def download(self):
        pass

    def process(self):
        pass

    def len(self):
        return len(self.data_list)

    def get(self, idx):
        return self.data_list[idx]