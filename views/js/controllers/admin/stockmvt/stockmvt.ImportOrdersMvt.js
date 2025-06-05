class ImportOrdersMvt {
    endPoints = null;
    abortSignal = null;
    abortController = null;
    totalOrders = 0;
    processedOrders = 0;
    errors = [];

    constructor(endPoints) {
        this.endPoints = endPoints;
        this.newAbortController();
    }

    async getOrdersState() {
        this.newAbortController();
        try {
            const response = await fetch(this.endPoints.fetchValidOrdersStateUrl, {
                signal: this.abortController.signal
            });
            const list = await response.json();

            return list;
        } catch (error) {
            //catch abort signal
            if (error.name === "AbortError") {
                console.log("Import stopped");
                return [];
            }
            console.error(error);
        }
    }

    async getOrders(orderStateIdsList = []) {
        this.newAbortController();
        try {
            const formData = new FormData();
            formData.append("orderStateIds", orderStateIdsList.join(","));
            const response = await fetch(this.endPoints.fetchOrdersUrl, {
                signal: this.abortController.signal,
                method: "POST",
                body: formData
            });
            const data = await response.json();
            this.totalOrders = data.length;
            this.processedOrders = 0;
            this.errors = [];

            const result = {
                totalOrders: data.length,
                processedOrders: 0,
                perc: 0,
                errors: []
            };

            return result;
        } catch (error) {
            //catch abort signal
            if (error.name === "AbortError") {
                console.log("Import stopped");
                return {
                    totalOrders: 0,
                    processedOrders: 0,
                    perc: 0,
                    errors: []
                };
            }
            console.error(error);
        }
    }

    async addOrdersMvt(orderList) {
        this.newAbortController();
        const chunk = orderList.slice(0, 10);
        const formData = new FormData();
        formData.append("order_ids", chunk.join(","));
        try {
            const response = await fetch(this.endPoints.importOrdersMvtUrl, {
                method: "POST",
                body: formData,
                signal: this.abortController.signal
            });
            const data = await response.json();
            this.processedOrders += chunk.length;
            this.errors = [...this.errors, ...data.errors];
            const result = {
                errors: this.errors,
                processedOrders: this.processedOrders,
                totalOrders: this.totalOrders,
                perc: this.getProgress().perc
            };
            console.log("ADD ORDERS MVT:", result);
            return result;
        } catch (error) {
            //catch abort signal
            if (error.name === "AbortError") {
                console.log("Import stopped");
                return {
                    errors: this.errors,
                    processedOrders: this.processedOrders,
                    totalOrders: this.totalOrders,
                    perc: this.getProgress().perc
                };
            }
            console.error(error);
        }
        return {
            errors: this.errors,
            processedOrders: this.processedOrders,
            totalOrders: this.totalOrders,
            perc: this.getProgress().perc
        };
    }

    newAbortController() {
        this.abortController = new AbortController();
    }

    getAbortSignal() {
        return this.abortController.signal;
    }

    stopImport() {
        this.abortController.abort();
    }

    getProgress() {
        if (this.processedOrders == this.totalOrders) {
            return {
                total: this.totalOrders,
                processed: this.processedOrders,
                perc: 100
            };
        }

        if (this.totalOrders == 0) {
            return {
                total: this.totalOrders,
                processed: this.processedOrders,
                perc: 0
            };
        }

        const perc = ((this.processedOrders / this.totalOrders) * 100).toFixed(2);
        return {
            total: this.totalOrders,
            processed: this.processedOrders,
            perc: perc
        };
    }

    getErrors() {
        return this.errors;
    }
}

export default ImportOrdersMvt;
