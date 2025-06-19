/**
 * Classe per importazione massiva di stock da file JSON con chiamate parallele e gestione chunk.
 * Gestisce anche il reset delle tabelle prima dell'importazione.
 * Tutti i metodi sono documentati in italiano.
 */

export default class InitStockImporter {
    jsonData = [];
    modalProgress = null;

    /**
     * @param {Object} endpoints - URL endpoint per inserimento chunk
     * @param {number} chunkSize - Dimensione chunk (default 100)
     */
    constructor(endpoints, chunkSize = 100, modalProgress = null) {
        this.endpointTruncate = endpoints.truncate;
        this.endpointGetJson = endpoints.getJson;
        this.endpointImport = endpoints.import;
        this.chunkSize = chunkSize;
        if (!modalProgress) {
            this.modalProgress = new window.ModuleClasses.modalProgress({
                title: "Importazione in corso",
                icon: "hourglass"
            });
        } else {
            this.modalProgress = modalProgress;
        }
    }

    /**
     * Divide un array in chunk di dimensione specificata
     * @param {Array} values
     * @param {number} chunkSize
     * @returns {Array}
     */
    chunkArray(values, chunkSize = 100) {
        const chunks = [];

        //controlla che values sia un array
        if (!Array.isArray(values)) return [];

        if (values.length == 0) return [];

        do {
            let chunk = values.splice(0, chunkSize);
            chunks.push(chunk);
        } while (values.length > 0);

        return chunks;
    }

    /**
     * Esegue la chiamata per svuotare le tabelle
     * @returns {Promise<any>}
     */
    async resetTables() {
        try {
            const response = await fetch(this.endpointTruncate, { method: "GET" });
            if (!response.ok) throw new Error("Errore nel reset delle tabelle");
            return await response.json();
        } catch (error) {
            throw new Error("Reset tabelle fallito: " + error.message);
        }
    }

    /**
     * Invia un chunk di dati all’endpoint di inserimento
     * @param {Array} chunk
     * @returns {Promise<{sent:number, inserted:number}>}
     */
    async sendChunk(chunk) {
        try {
            const response = await fetch(this.endpointImport, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(chunk),
                signal: this.modalProgress.abortController.signal
            });
            if (!response.ok) throw new Error("Errore nell’invio chunk");
            return await response.json(); // { sent: N, inserted: M }
        } catch (error) {
            throw new Error("Errore invio chunk: " + error.message);
        }
    }

    /**
     * Avvia la procedura completa di importazione
     * @param {function} onProgress - Callback opzionale per aggiornare il progresso
     * @returns {Promise<{totalSent: number, totalInserted: number}>}
     */
    async run(onProgress = null, maxCallableThreads = 30) {
        let totalSent = 0;
        let totalInserted = 0;

        this.modalProgress.open();

        // Recupera i dati JSON
        const response = await fetch(this.endpointGetJson);
        const result = await response.json();
        this.jsonData = result.data || [];

        try {
            // 1. Svuota le tabelle
            await this.resetTables();
        } catch (error) {
            if (onProgress) onProgress({ error: true, message: error.message });
            throw error;
        }

        // 2. Split in chunk
        const chunks = this.chunkArray(this.jsonData, this.chunkSize);

        // 3. Pool di promise per limitare le chiamate simultanee
        let active = 0;
        let i = 0;
        let errors = [];
        let completed = 0;

        return new Promise((resolve, reject) => {
            const next = () => {
                if (i >= chunks.length && active === 0) {
                    // Tutto finito
                    resolve({ totalSent, totalInserted, errors });
                    return;
                }
                while (active < maxCallableThreads && i < chunks.length) {
                    const chunk = chunks[i++];
                    active++;
                    this.sendChunk(chunk)
                        .then((res) => {
                            totalSent++;
                            if (res && res.inserted) totalInserted += res.inserted;
                            completed++;
                            if (onProgress) {
                                onProgress({
                                    percent: Math.round((completed / chunks.length) * 100),
                                    sent: totalSent,
                                    inserted: totalInserted,
                                    total: chunks.length
                                });
                            }
                        })
                        .catch((err) => {
                            errors.push(err);
                            if (onProgress) {
                                onProgress({
                                    error: true,
                                    message: err.message || "Errore invio chunk"
                                });
                            }
                        })
                        .finally(() => {
                            active--;
                            next();
                        });
                }
            };
            next();
        });
    }
}

/*
// Esempio di utilizzo:
import InitStockImporter from './stockmvt.ImportInitStock.js';

const importer = new InitStockImporter(
    '/modules/mpstockadv/ajax/insert-stock', // endpoint inserimento
    '/modules/mpstockadv/ajax/reset-stock',  // endpoint reset
    jsonData,                                // array JS con i dati
    100                                      // chunk size (opzionale)
);

importer.run(progress => {
    if(progress.error) {
        console.error(progress.message);
    } else {
        console.log(`Chunk ${progress.current}/${progress.total} - Inviati: ${progress.totalSent}, Inseriti: ${progress.totalInserted}`);
    }
}).then(({ totalSent, totalInserted }) => {
    alert(`Importazione completata! Inviati: ${totalSent}, Inseriti: ${totalInserted}`);
}).catch(err => {
    alert('Errore: ' + err.message);
});
*/
