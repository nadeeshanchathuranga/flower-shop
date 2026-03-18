<template>
  <TransitionRoot as="template" :show="open">
    <Dialog class="relative z-10" @close="$emit('update:open', false)">
      <!-- Modal Overlay -->
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div
          class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
        />
      </TransitionChild>

      <!-- Modal Content -->
      <div class="fixed inset-0 z-10 flex items-center justify-center">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0 scale-95"
          enter-to="opacity-100 scale-100"
          leave="ease-in duration-200"
          leave-from="opacity-100 scale-100"
          leave-to="opacity-0 scale-95"
        >
          <DialogPanel
            class="bg-black border-4 border-blue-600 rounded-[20px] shadow-xl w-5/6 lg:w-3/6 p-10 text-center"
          >
            <!-- Modal Title -->
            <DialogTitle class="text-xl font-bold text-white">
              Edit Wastage
            </DialogTitle>

            <form @submit.prevent="submit">
              <!-- Modal Form -->
              <div class="grid grid-cols-2 gap-6 mt-6 text-left">
                <!-- Product -->
                <div class="col-span-2">
                  <label class="block text-sm font-medium text-gray-300">
                    Product:
                  </label>
                  <select
                    v-model="form.product_id"
                    id="product_id"
                    required
                    class="w-full px-4 py-2 mt-2 text-black rounded-md focus:outline-none focus:ring focus:ring-blue-600"
                  >
                    <option value="">Select a product</option>
                    <option
                      v-for="product in products"
                      :key="product.id"
                      :value="product.id"
                    >
                      {{ product.name }} - Stock: {{ product.stock_quantity }}
                      ({{ product.category?.name || "N/A" }})
                    </option>
                  </select>
                  <span v-if="form.errors.product_id" class="mt-2 text-red-500">
                    {{ form.errors.product_id }}
                  </span>
                </div>

                <!-- Quantity -->
                <div>
                  <label class="block text-sm font-medium text-gray-300">
                    Quantity:
                  </label>
                  <input
                    v-model="form.quantity"
                    type="number"
                    id="quantity"
                    min="1"
                    required
                    class="w-full px-4 py-2 mt-2 text-black rounded-md focus:outline-none focus:ring focus:ring-blue-600"
                  />
                  <span v-if="form.errors.quantity" class="mt-2 text-red-500">
                    {{ form.errors.quantity }}
                  </span>
                </div>

                <!-- Wastage Date -->
                <div>
                  <label class="block text-sm font-medium text-gray-300">
                    Wastage Date:
                  </label>
                  <input
                    v-model="form.wastage_date"
                    type="date"
                    id="wastage_date"
                    :max="today"
                    required
                    class="w-full px-4 py-2 mt-2 text-black rounded-md focus:outline-none focus:ring focus:ring-blue-600"
                  />
                  <span
                    v-if="form.errors.wastage_date"
                    class="mt-2 text-red-500"
                  >
                    {{ form.errors.wastage_date }}
                  </span>
                </div>

                <!-- Reason -->
                <div class="col-span-2">
                  <label class="block text-sm font-medium text-gray-300">
                    Reason:
                  </label>
                  <textarea
                    v-model="form.reason"
                    id="reason"
                    rows="3"
                    class="w-full px-4 py-2 mt-2 text-black rounded-md focus:outline-none focus:ring focus:ring-blue-600"
                    placeholder="Enter reason for wastage (optional)"
                  ></textarea>
                  <span v-if="form.errors.reason" class="mt-2 text-red-500">
                    {{ form.errors.reason }}
                  </span>
                </div>
              </div>

              <!-- Modal Buttons -->
              <div class="mt-6 space-x-4 text-center">
                <button
                  @click="
                    () => {
                      playClickSound();
                    }
                  "
                  class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700"
                  type="submit"
                >
                  Save
                </button>
                <button
                  type="button"
                  class="px-4 py-2 text-gray-700 bg-gray-300 rounded hover:bg-gray-400"
                  @click="
                    () => {
                      playClickSound();
                      emit('update:open', false);
                    }
                  "
                >
                  Cancel
                </button>
              </div>
            </form>
          </DialogPanel>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";
import { computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";

const playClickSound = () => {
  const clickSound = new Audio("/sounds/click-sound.mp3");
  clickSound.play();
};

const emit = defineEmits(["update:open"]);

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  selectedWastage: {
    type: Object,
    default: null,
  },
  products: {
    type: Array,
    required: true,
  },
});

const form = useForm({
  product_id: "",
  quantity: 1,
  wastage_date: "",
  reason: "",
});

const today = computed(() => {
  return new Date().toISOString().split("T")[0];
});

// Watch for changes in selectedWastage and populate the form
watch(
  () => props.selectedWastage,
  (newWastage) => {
    if (newWastage) {
      form.product_id = newWastage.product_id || "";
      form.quantity = newWastage.quantity || 1;
      form.wastage_date = newWastage.wastage_date || "";
      form.reason = newWastage.reason || "";
    }
  },
  { immediate: true }
);

const submit = () => {
  if (!props.selectedWastage?.id) return;

  form.put(`/wastages/${props.selectedWastage.id}`, {
    onSuccess: () => {
      emit("update:open", false);
    },
  });
};
</script>
