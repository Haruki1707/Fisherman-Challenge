<script setup>

import Layout from "@/Layouts/Layout.vue";
import {Link, useForm} from "@inertiajs/vue3";
import {ref, watch} from "vue";
import {throttle} from "lodash";

const props = defineProps(['customers', 'links', 'filters', 'search_types']);

const inputFile = ref(null);

const fileForm = useForm({
    excel_file: null
});

const searchForm = useForm({
    search: props.filters.search,
    search_type: props.filters.search_type ?? props.search_types[0],
});

watch(() => fileForm.excel_file, function () {
    if (fileForm.excel_file == null) {
        return;
    }

    fileForm.post(route('customer.import'), {
        onFinish: () => {
            fileForm.defaults('excel_file', null);
            fileForm.reset();
            inputFile.value.value = null;
        }
    });
});

watch(() => [searchForm.search, searchForm.search_type], throttle(function () {
    if (searchForm.search == null) {
        return;
    }

    searchForm.get(route('customer.index'), {
        replace: true,
        preserveState: true,
        preserveScroll: true,
    });
}));

const addDashes = (number) => {
    return number.toString().replace(/^(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
};
</script>

<template>
    <Layout>
        <div class="row">
            <!-- Button trigger modal -->
            <button class="btn btn-primary" data-bs-target="#staticBackdrop" data-bs-toggle="modal" type="button">
                Import Customers from Excel
            </button>

            <!-- Modal -->
            <div id="staticBackdrop" aria-hidden="true" aria-labelledby="staticBackdropLabel" class="modal fade" data-bs-backdrop="static"
                 data-bs-keyboard="false" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 id="staticBackdropLabel" class="modal-title fs-5">Customers Importer</h1>
                            <button aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"></button>
                        </div>
                        <div class="modal-body my-2">
                            <label class="form-label">Upload the excel file with customers that you want to
                                import</label>
                            <input ref="inputFile" :disabled="fileForm.isDirty"
                                   accept="application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                   class="form-control" type="file"
                                   @input="fileForm.excel_file = $event.target.files[0]">

                            <div v-if="fileForm.progress" :aria-valuenow="fileForm.progress.percentage" aria-label="Basic example"
                                 aria-valuemax="100" aria-valuemin="0"
                                 class="progress my-2" role="progressbar" style="height: 3px">
                                <div :style="'width: ' + fileForm.progress.percentage + '%'" class="progress-bar"></div>
                            </div>
                        </div>
                        <div v-if="Object.keys(fileForm.errors).length > 0 || fileForm.recentlySuccessful"
                             class="modal-footer my-2 mx-auto">
                            <span v-for="error in fileForm.errors" class="text-danger">{{ error }}</span>
                            <span v-if="fileForm.recentlySuccessful" class="text-success">All customers successfully imported from the Excel</span>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="input-group my-4">
                    <label class="input-group-text">Search</label>
                    <input v-model="searchForm.search" class="form-control" type="text">
                    <select v-model="searchForm.search_type" class="form-select">
                        <option v-for="type in search_types" :value="type">by {{ type }}</option>
                    </select>
                    <button class="btn btn-outline-secondary" type="button" @click="searchForm.search = null">Clear
                    </button>
                </div>
            </div>

            <table class="table">
                <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Address</th>
                    <th scope="col">Phone Number</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="customer in customers">
                    <td>{{ customer.name }}</td>
                    <td>{{ customer.email }}</td>
                    <td>{{ customer.full_address }}</td>
                    <td>{{ addDashes(customer.phone_number) }}</td>
                </tr>
                </tbody>
            </table>

            <div v-if="links.length > 3" class="input-group my-3 justify-content-center">
                <template v-for="link in links">
                    <Link v-if="link.url !== null && !link.active" :href="link.url ?? ''"
                          class="btn btn-outline-secondary"
                          v-html="link.label"/>
                    <button v-else :class="{
                            'btn-outline-secondary': !link.active,
                            'btn-secondary': link.active,
                        }" class="btn" disabled v-html="link.label"></button>
                </template>
            </div>
        </div>
    </Layout>
</template>
