<template>
  <Head title="Record Wastage" />
  <Banner />
  <div
    class="flex flex-col items-center justify-start min-h-screen py-8 space-y-8 bg-gray-100 md:px-36 px-16"
  >
    <Header />

    <div class="w-full md:w-5/6 py-12 space-y-12">
      <div class="flex items-center w-full h-16 space-x-4 rounded-2xl">
        <Link :href="route('wastages.index')">
          <img src="/images/back-arrow.png" class="w-14 h-14" alt="Back" />
        </Link>
        <p class="text-4xl font-bold tracking-wide text-black uppercase">
          Record New Wastage
        </p>
      </div>

      <div class="p-8 bg-white rounded-lg shadow-md">
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Product Selection -->
          <div>
            <label
              for="product_id"
              class="block mb-2 text-lg font-semibold text-gray-700"
            >
              Product <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.product_id"
              id="product_id"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
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
            <span v-if="form.errors.product_id" class="text-sm text-red-500">
              {{ form.errors.product_id }}
            </span>
          </div>

          <!-- Quantity -->
          <div>
            <label
              for="quantity"
              class="block mb-2 text-lg font-semibold text-gray-700"
            >
              Quantity <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.quantity"
              type="number"
              id="quantity"
              min="1"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
            <span v-if="form.errors.quantity" class="text-sm text-red-500">
              {{ form.errors.quantity }}
            </span>
          </div>

          <!-- Wastage Date -->
          <div>
            <label
              for="wastage_date"
              class="block mb-2 text-lg font-semibold text-gray-700"
            >
              Wastage Date <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.wastage_date"
              type="date"
              id="wastage_date"
              :max="today"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
            <span v-if="form.errors.wastage_date" class="text-sm text-red-500">
              {{ form.errors.wastage_date }}
            </span>
          </div>

          <!-- Reason -->
          <div>
            <label
              for="reason"
              class="block mb-2 text-lg font-semibold text-gray-700"
            >
              Reason
            </label>
            <textarea
              v-model="form.reason"
              id="reason"
              rows="4"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter reason for wastage (optional)"
            ></textarea>
            <span v-if="form.errors.reason" class="text-sm text-red-500">
              {{ form.errors.reason }}
            </span>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-end space-x-4">
            <Link
              :href="route('wastages.index')"
              class="px-6 py-3 text-lg font-semibold text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300"
            >
              Cancel
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-3 text-lg font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              <span v-if="form.processing">Saving...</span>
              <span v-else>Record Wastage</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <Footer />
</template>

<script setup>
import { ref, computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import Header from "@/Components/custom/Header.vue";
import Footer from "@/Components/custom/Footer.vue";
import Banner from "@/Components/Banner.vue";

const props = defineProps({
  products: Array,
});

const form = useForm({
  product_id: "",
  quantity: 1,
  wastage_date: new Date().toISOString().split("T")[0],
  reason: "",
});

const today = computed(() => {
  return new Date().toISOString().split("T")[0];
});

const submit = () => {
  form.post(route("wastages.store"));
};
</script>
