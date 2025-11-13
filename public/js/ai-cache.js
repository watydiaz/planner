/**
 * Caché simple para respuestas de IA
 * Reduce llamadas repetidas a Gemini
 */
class AICache {
    static store = new Map();
    static maxSize = 50; // Máximo 50 respuestas en caché
    static ttl = 3600000; // 1 hora en milisegundos

    /**
     * Generar key única para caché
     */
    static getKey(action, params) {
        const normalized = JSON.stringify(params).toLowerCase().trim();
        return `${action}:${normalized}`;
    }

    /**
     * Obtener del caché
     */
    static get(action, params) {
        const key = this.getKey(action, params);
        const cached = this.store.get(key);
        
        if (!cached) return null;
        
        // Verificar si expiró
        if (Date.now() - cached.timestamp > this.ttl) {
            this.store.delete(key);
            return null;
        }
        
        console.log('✅ Respuesta desde caché (instantánea)');
        return cached.data;
    }

    /**
     * Guardar en caché
     */
    static set(action, params, data) {
        const key = this.getKey(action, params);
        
        // Limpiar caché si está lleno
        if (this.store.size >= this.maxSize) {
            const firstKey = this.store.keys().next().value;
            this.store.delete(firstKey);
        }
        
        this.store.set(key, {
            data: data,
            timestamp: Date.now()
        });
    }

    /**
     * Limpiar caché completo
     */
    static clear() {
        this.store.clear();
        console.log('🗑️ Caché de IA limpiado');
    }
}

window.AICache = AICache;
