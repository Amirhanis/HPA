<template>
    <AdminLayout>
        <div class="p-4">
            <h1 class="text-2xl font-semibold mb-4">Orders</h1>

            <div class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs uppercase bg-gray-50 text-gray-700">
                        <tr>
                            <th class="px-4 py-3">Order #</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Shipment</th>
                            <th class="px-4 py-3">Payment Action</th>
                            <th class="px-4 py-3">Add/Update Tracking</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders.data" :key="order.id" class="border-t">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ order.id }}</td>
                            <td class="px-4 py-3">RM {{ order.total_price }}</td>
                            <td class="px-4 py-3">{{ order.status }}</td>

                            <td class="px-4 py-3">
                                <div v-if="order.shipment">
                                    <div><strong>{{ order.shipment.courier_code }}</strong></div>
                                    <div>{{ order.shipment.tracking_number }}</div>
                                    <div class="text-xs text-gray-500">{{ order.shipment.delivery_status ?? 'unknown' }}</div>
                                </div>
                                <div v-else class="text-gray-500 text-xs">No tracking yet</div>
                            </td>
                            <td class="px-4 py-3">
                                 <button
                                    v-if="order.status === 'unpaid' || order.status === 'pending'"
                                    class="bg-green-600 text-white px-3 py-1 rounded text-xs"
                                    @click="markAsPaid(order.id)"
                                >
                                    Mark Paid
                                </button>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex gap-2 items-center">
                                    <input
                                        v-model="draft[order.id].tracking_number"
                                        class="border rounded px-2 py-1 w-44"
                                        placeholder="tracking number"
                                        type="text"
                                    />
                                    <input
                                        v-model="draft[order.id].courier_code"
                                        class="border rounded px-2 py-1 w-36"
                                        placeholder="courier code"
                                        list="courier-codes"
                                        type="text"
                                    />
                                    <button
                                        class="bg-blue-600 text-white px-3 py-1 rounded"
                                        @click="save(order.id)"
                                    >
                                        Save
                                    </button>
                                </div>

                                <datalist id="courier-codes">
                                    <option v-for="c in couriers" :key="c.code" :value="c.code">{{ c.name }}</option>
                                </datalist>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap gap-2 mt-4">
                <a
                    v-for="l in orders.links"
                    :key="l.url ?? l.label"
                    :href="l.url ?? '#'"
                    class="px-3 py-1 rounded border text-sm"
                    :class="{
                        'bg-gray-200 pointer-events-none': l.active,
                        'text-gray-400 pointer-events-none': !l.url
                    }"
                    v-html="l.label"
                />
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from './Components/AdminLayout.vue'
import { reactive, ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    orders: Object,
})

const draft = reactive({})

const couriers = ref([])

onMounted(async () => {
    try {
        const res = await axios.get(route('admin.couriers.index'))
        couriers.value = res.data?.data ?? []
    } catch (e) {
        couriers.value = []
    }
})

props.orders.data.forEach((o) => {
    draft[o.id] = {
        tracking_number: o.shipment?.tracking_number ?? '',
        courier_code: o.shipment?.courier_code ?? '',
    }
})

function save(orderId) {
    router.post(route('admin.orders.shipment.store', orderId), {
        tracking_number: draft[orderId].tracking_number,
        courier_code: draft[orderId].courier_code,
    }, {
        preserveScroll: true,
    })
}

function markAsPaid(orderId) {
    if (confirm('Are you sure you want to mark this order as PAID?')) {
        router.post(route('admin.orders.mark_paid', orderId), {}, {
            preserveScroll: true,
             onSuccess: () => alert('Order marked as paid'),
        })
    }
}
</script>
