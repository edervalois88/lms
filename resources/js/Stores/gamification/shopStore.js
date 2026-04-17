import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useShopStore = defineStore('shop', () => {
  // State
  const catalog = ref([]);
  const inventory = ref([]);
  const equipped = ref({});
  const loading = ref(false);

  // Computed
  const isOwned = computed(() => {
    return (rewardItemId) => {
      return inventory.value.some((item) => item.reward_item_id === rewardItemId);
    };
  });

  const isEquipped = computed(() => {
    return (code) => {
      return Object.values(equipped.value).some((item) => item?.code === code);
    };
  });

  // Actions
  const fetchCatalog = async () => {
    loading.value = true;
    try {
      const response = await axios.get('/rewards/catalog');
      catalog.value = response.data.map((item) => ({
        ...item,
        cost: item.cost_xp,
      }));
    } finally {
      loading.value = false;
    }
  };

  const fetchInventory = async () => {
    loading.value = true;
    try {
      const response = await axios.get('/rewards/inventory');
      inventory.value = response.data.inventory;
      equipped.value = response.data.equipped;
    } finally {
      loading.value = false;
    }
  };

  const purchase = async (rewardItemId, idempotencyKey) => {
    loading.value = true;
    try {
      const response = await axios.post('/rewards/purchase', {
        reward_item_id: rewardItemId,
        idempotency_key: idempotencyKey,
      });
      await fetchInventory();
      return response.data;
    } finally {
      loading.value = false;
    }
  };

  const equip = async (userRewardItemId) => {
    loading.value = true;
    try {
      const response = await axios.post('/rewards/equip', {
        user_reward_item_id: userRewardItemId,
      });
      // Actualizar equipped con la respuesta del servidor si es necesario
      // Por ahora simplemente retornamos la respuesta
      return response.data;
    } finally {
      loading.value = false;
    }
  };

  return {
    catalog,
    inventory,
    equipped,
    loading,
    isOwned,
    isEquipped,
    fetchCatalog,
    fetchInventory,
    purchase,
    equip,
  };
});
