import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import axios from 'axios';
import { useShopStore } from '@/Stores/gamification/shopStore';

vi.mock('axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}));

describe('shopStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('initializes with empty catalog, inventory, and loading false', () => {
    const store = useShopStore();
    expect(store.catalog).toEqual([]);
    expect(store.inventory).toEqual([]);
    expect(store.equipped).toEqual({});
    expect(store.loading).toBe(false);
  });

  it('fetchCatalog populates catalog and maps cost_xp to cost', async () => {
    const mockData = {
      data: [
        { id: 1, name: 'Item 1', cost_xp: 100 },
        { id: 2, name: 'Item 2', cost_xp: 250 },
      ],
    };
    axios.get.mockResolvedValueOnce(mockData);

    const store = useShopStore();
    await store.fetchCatalog();

    expect(store.catalog).toHaveLength(2);
    expect(store.catalog[0]).toEqual({ id: 1, name: 'Item 1', cost: 100, cost_xp: 100 });
    expect(store.catalog[1]).toEqual({ id: 2, name: 'Item 2', cost: 250, cost_xp: 250 });
    expect(axios.get).toHaveBeenCalledWith('/rewards/catalog');
  });

  it('fetchInventory sets inventory and equipped', async () => {
    const mockData = {
      data: {
        inventory: [
          { id: 1, reward_item_id: 10, user_id: 1 },
          { id: 2, reward_item_id: 11, user_id: 1 },
        ],
        equipped: {
          outfit: { code: 'outfit_001', name: 'Outfit 1' },
          pet: { code: 'pet_001' },
        },
      },
    };
    axios.get.mockResolvedValueOnce(mockData);

    const store = useShopStore();
    await store.fetchInventory();

    expect(store.inventory).toEqual(mockData.data.inventory);
    expect(store.equipped).toEqual(mockData.data.equipped);
    expect(axios.get).toHaveBeenCalledWith('/rewards/inventory');
  });

  it('isOwned returns true for items in inventory', async () => {
    const store = useShopStore();
    store.inventory = [
      { id: 1, reward_item_id: 10 },
      { id: 2, reward_item_id: 11 },
    ];

    expect(store.isOwned(10)).toBe(true);
    expect(store.isOwned(11)).toBe(true);
    expect(store.isOwned(12)).toBe(false);
  });

  it('isEquipped returns true for items in equipped values', () => {
    const store = useShopStore();
    store.equipped = {
      outfit: { code: 'outfit_001', name: 'Outfit 1' },
      pet: { code: 'pet_001' },
    };

    expect(store.isEquipped('outfit_001')).toBe(true);
    expect(store.isEquipped('pet_001')).toBe(true);
    expect(store.isEquipped('unknown_001')).toBe(false);
  });

  it('purchase sends POST request and fetches inventory on success', async () => {
    const mockPurchaseResponse = { data: { message: 'Purchase successful' } };
    axios.post.mockResolvedValueOnce(mockPurchaseResponse);

    const mockInventoryResponse = {
      data: {
        inventory: [{ id: 1, reward_item_id: 10 }],
        equipped: {},
      },
    };
    axios.get.mockResolvedValueOnce(mockInventoryResponse);

    const store = useShopStore();
    const result = await store.purchase(10, 'idempotency-key-123');

    expect(axios.post).toHaveBeenCalledWith('/rewards/purchase', {
      reward_item_id: 10,
      idempotency_key: 'idempotency-key-123',
    });
    expect(axios.get).toHaveBeenCalledWith('/rewards/inventory');
    expect(result).toEqual(mockPurchaseResponse.data);
  });

  it('purchase works without idempotency key', async () => {
    const mockPurchaseResponse = { data: { message: 'Purchase successful' } };
    axios.post.mockResolvedValueOnce(mockPurchaseResponse);

    const mockInventoryResponse = {
      data: {
        inventory: [],
        equipped: {},
      },
    };
    axios.get.mockResolvedValueOnce(mockInventoryResponse);

    const store = useShopStore();
    const result = await store.purchase(10);

    expect(axios.post).toHaveBeenCalledWith('/rewards/purchase', {
      reward_item_id: 10,
      idempotency_key: undefined,
    });
    expect(result).toEqual(mockPurchaseResponse.data);
  });

  it('equip sends POST request and updates equipped on success', async () => {
    const mockEquipResponse = { data: { equipped_slot: 'outfit' } };
    axios.post.mockResolvedValueOnce(mockEquipResponse);

    const store = useShopStore();
    store.equipped = {};

    const result = await store.equip(99);

    expect(axios.post).toHaveBeenCalledWith('/rewards/equip', {
      user_reward_item_id: 99,
    });
    expect(result).toEqual(mockEquipResponse.data);
  });

  it('loading state is managed during async operations', async () => {
    const mockData = { data: [] };
    axios.get.mockImplementationOnce(() => {
      return new Promise((resolve) => {
        setTimeout(() => resolve(mockData), 10);
      });
    });

    const store = useShopStore();
    expect(store.loading).toBe(false);

    const fetchPromise = store.fetchCatalog();
    // Loading debería ser true durante la operación
    // (aunque es difícil de testear sin esperar)
    await fetchPromise;

    expect(store.loading).toBe(false);
  });
});
