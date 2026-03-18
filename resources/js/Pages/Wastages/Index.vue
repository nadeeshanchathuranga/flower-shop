<style>
.dataTables_wrapper .dataTables_paginate {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 20px;
}

#WastageTable_filter {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  margin-bottom: 16px;
}

#WastageTable_filter label {
  font-size: 17px;
  color: #000000;
  display: flex;
  align-items: center;
}

#WastageTable_filter input[type="search"] {
  font-weight: 400;
  padding: 9px 15px;
  font-size: 14px;
  color: #000000cc;
  border: 1px solid rgb(209 213 219);
  border-radius: 5px;
  background: #fff;
  outline: none;
  transition: all 0.5s ease;
}
#WastageTable_filter input[type="search"]:focus {
  outline: none;
  border: 1px solid #4b5563;
  box-shadow: none;
}

#WastageTable_filter {
  float: left;
}

.dataTables_wrapper {
  margin-bottom: 10px;
}
</style>

<template>
  <Head title="Wastages" />
  <Banner />
  <div
    class="flex flex-col items-center justify-start min-h-screen py-8 space-y-8 bg-gray-100 md:px-36 px-16"
  >
    <Header />

    <div class="w-full md:w-5/6 py-12 space-y-24">
      <div class="flex items-center justify-between float-end">
        <p class="text-3xl italic font-bold text-black">
          <span class="px-4 py-1 mr-3 text-white bg-black rounded-xl">{{
            totalWastages
          }}</span>
          <span class="text-xl">/ Total Wastages</span>
        </p>
      </div>

      <div class="flex md:flex-row flex-col w-full">
        <div class="flex items-center w-full h-16 space-x-4 rounded-2xl">
          <Link href="/">
            <img src="/images/back-arrow.png" class="w-14 h-14" alt="Back" />
          </Link>
          <p class="text-4xl font-bold tracking-wide text-black uppercase">
            Flower Wastages
          </p>
        </div>
        <div class="flex justify-end w-full">
          <p
            @click="
              () => {
                if (HasRole(['Admin', 'Manager'])) {
                  isCreateModalOpen = true;
                }
              }
            "
            :class="
              HasRole(['Admin', 'Manager'])
                ? 'md:px-12 py-4 px-4 md:text-2xl font-bold tracking-wider text-white uppercase bg-blue-600 rounded-xl cursor-pointer'
                : 'md:px-12 py-4 px-4 md:text-2xl font-bold tracking-wider text-white uppercase bg-blue-600 cursor-not-allowed rounded-xl'
            "
            :title="
              HasRole(['Admin', 'Manager'])
                ? ''
                : 'You do not have permission to add wastages'
            "
          >
            <i class="md:pr-4 ri-add-circle-fill"></i> Record Wastage
          </p>
        </div>
      </div>

      <template v-if="allwastages && allwastages.length > 0">
        <div class="overflow-x-auto">
          <table
            id="WastageTable"
            class="w-full text-gray-700 bg-white border border-gray-300 rounded-lg shadow-md table-auto"
          >
            <thead>
              <tr
                class="bg-gradient-to-r from-blue-600 via-blue-500 to-blue-600 text-[16px] text-white border-b border-blue-700"
              >
                <th class="p-4 font-semibold tracking-wide text-left uppercase">
                  Date
                </th>
                <th class="p-4 font-semibold tracking-wide text-left uppercase">
                  Product
                </th>
                <th class="p-4 font-semibold tracking-wide text-left uppercase">
                  Quantity
                </th>
                <th class="p-4 font-semibold tracking-wide text-left uppercase">
                  Reason
                </th>
                <th class="p-4 font-semibold tracking-wide text-left uppercase">
                  Recorded By
                </th>
                <th
                  class="p-4 font-semibold tracking-wide text-center uppercase"
                >
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="text-[13px] font-normal">
              <tr
                v-for="wastage in allwastages"
                :key="wastage.id"
                class="transition duration-200 ease-in-out hover:bg-gray-200 hover:shadow-lg"
              >
                <td class="p-4 font-bold border-t border-gray-200">
                  {{ formatDate(wastage.wastage_date) }}
                </td>
                <td class="p-4 border-t border-gray-200">
                  {{ wastage.product?.name || "N/A" }}
                </td>
                <td class="p-4 border-t border-gray-200">
                  {{ wastage.quantity }}
                </td>
                <td class="p-4 border-t border-gray-200">
                  {{ wastage.reason || "N/A" }}
                </td>
                <td class="p-4 border-t border-gray-200">
                  {{ wastage.user?.name || "N/A" }}
                </td>
                <td class="p-4 text-center border-t border-gray-200">
                  <div class="inline-flex items-center w-full space-x-3">
                    <button
                      v-if="HasRole(['Admin', 'Manager'])"
                      @click="openEditModal(wastage)"
                      class="w-full px-4 py-2 font-medium text-[14px] tracking-wider text-white bg-gradient-to-r from-green-500 to-green-400 transition duration-150 ease-in-out rounded-md hover:from-green-600 hover:to-green-500"
                    >
                      Edit
                    </button>

                    <button
                      v-if="HasRole(['Admin'])"
                      @click="openDeleteModal(wastage)"
                      class="w-full px-4 py-2 font-medium text-[14px] tracking-wider text-white bg-gradient-to-r from-red-500 to-red-400 transition duration-150 ease-in-out rounded-md hover:from-red-600 hover:to-red-500"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
      <template v-else>
        <div class="col-span-4 text-center text-blue-500">
          <p class="text-center text-red-500 text-[17px]">
            No Wastages recorded yet
          </p>
        </div>
      </template>
    </div>
  </div>
  <Footer />

  <WastageCreateModal
    :products="products"
    v-model:open="isCreateModalOpen"
  />

  <WastageUpdateModal
    :products="products"
    :selected-wastage="selectedWastage"
    v-model:open="isEditModalOpen"
  />

  <WastageDeleteModal
    :wastage="selectedWastage"
    v-model:open="isDeleteModalOpen"
  />
</template>

<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import Header from "@/Components/custom/Header.vue";
import Footer from "@/Components/custom/Footer.vue";
import Banner from "@/Components/Banner.vue";
import WastageCreateModal from "@/Components/custom/WastageCreateModal.vue";
import WastageUpdateModal from "@/Components/custom/WastageUpdateModal.vue";
import WastageDeleteModal from "@/Components/custom/WastageDeleteModal.vue";
import { HasRole } from "@/Utils/Permissions";

defineProps({
  allwastages: Array,
  totalWastages: Number,
  products: Array,
});

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedWastage = ref(null);

const openEditModal = (wastage) => {
  selectedWastage.value = wastage;
  isEditModalOpen.value = true;
};

const openDeleteModal = (wastage) => {
  selectedWastage.value = wastage;
  isDeleteModalOpen.value = true;
};

const formatDate = (date) => {
  if (!date) return "N/A";
  const d = new Date(date);
  return d.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

$(document).ready(function () {
  let table = $("#WastageTable").DataTable({
    dom: "Bfrtip",
    pageLength: 10,
    buttons: [],
    order: [[0, "desc"]],
    columnDefs: [
      {
        targets: [5],
        searchable: false,
        orderable: false,
      },
    ],
    initComplete: function () {
      let searchInput = $("div.dataTables_filter input");
      searchInput.attr("placeholder", "Search ...");
    },
    language: {
      search: "",
    },
  });
});
</script>
