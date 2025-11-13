/**
 * AI Assistant Module - Integración con Google Gemini
 * Funciones para asistencia inteligente en gestión de tareas
 */

class AIAssistant {
    constructor() {
        this.baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
        this.isProcessing = false;
    }
    
    /**
     * Obtener CSRF token
     */
    getCSRF() {
        return window.csrf || window.CSRF || '';
    }

    /**
     * Generar descripción automática basada en el título
     */
    async generarDescripcion(titulo) {
        if (!titulo || titulo.trim().length < 3) {
            throw new Error('El título debe tener al menos 3 caracteres');
        }

        // Verificar caché primero
        const cached = window.AICache?.get('descripcion', {titulo});
        if (cached) {
            return cached;
        }

        this.isProcessing = true;
        
        try {
            const url = `${this.baseUrl}/?action=ai_generar_descripcion`;
            console.log('Llamando a:', url);
            console.log('CSRF Token:', this.getCSRF());
            
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    titulo: titulo.trim(),
                    csrf: this.getCSRF()
                })
            });

            console.log('Response status:', response.status);
            const data = await response.json();
            console.log('Response data:', data);
            console.log('Details:', JSON.stringify(data.details, null, 2));
            
            if (!data.success) {
                const errorMsg = `${data.error}\nDetalles: ${JSON.stringify(data.details)}`;
                throw new Error(errorMsg);
            }

            const result = data.text || data.data?.descripcion || '';
            
            // Guardar en caché
            window.AICache?.set('descripcion', {titulo}, result);
            
            return result;
        } catch (error) {
            console.error('Error AI generarDescripcion:', error);
            throw error;
        } finally {
            this.isProcessing = false;
        }
    }

    /**
     * Estimar complejidad y story points
     */
    async estimarComplejidad(titulo, descripcion = '') {
        // Verificar caché
        const cached = window.AICache?.get('complejidad', {titulo, descripcion});
        if (cached) {
            return cached;
        }
        
        this.isProcessing = true;
        
        try {
            console.log('Estimando complejidad para:', titulo, descripcion);
            
            const response = await fetch(`${this.baseUrl}/?action=ai_estimar_complejidad`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    titulo: titulo.trim(),
                    descripcion: descripcion.trim(),
                    csrf: this.getCSRF()
                })
            });

            console.log('Response status:', response.status);
            const data = await response.json();
            console.log('Response data:', data);
            
            if (!data.success) {
                throw new Error(data.error || data.details || 'Error al estimar complejidad');
            }

            // Intentar obtener story points de múltiples fuentes
            let storyPoints = data.data?.story_points;
            let razon = data.data?.razon || 'Estimación automática';
            
            // Si no hay data pero hay texto, intentar extraer
            if (!storyPoints && data.text) {
                const match = data.text.match(/\b([1358]|13|21)\b/);
                storyPoints = match ? parseInt(match[1]) : 3;
                razon = 'Extraído del análisis';
            }
            
            const result = {
                storyPoints: storyPoints || 3,
                razon: razon
            };
            
            // Guardar en caché
            window.AICache?.set('complejidad', {titulo, descripcion}, result);
            
            return result;
        } catch (error) {
            console.error('Error AI estimarComplejidad:', error);
            throw error;
        } finally {
            this.isProcessing = false;
        }
    }

    /**
     * Generar subtareas automáticamente
     */
    async generarSubtareas(titulo, descripcion = '') {
        this.isProcessing = true;
        
        try {
            const response = await fetch(`${this.baseUrl}/?action=ai_generar_subtareas`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    titulo: titulo.trim(),
                    descripcion: descripcion.trim(),
                    csrf: this.getCSRF()
                })
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Error al generar subtareas');
            }

            return data.data?.subtareas || [];
        } catch (error) {
            console.error('Error AI generarSubtareas:', error);
            throw error;
        } finally {
            this.isProcessing = false;
        }
    }

    /**
     * Sugerir categoría automáticamente
     */
    async sugerirCategoria(titulo, descripcion = '') {
        // Verificar caché
        const cached = window.AICache?.get('categoria', {titulo, descripcion});
        if (cached) {
            return cached;
        }
        
        this.isProcessing = true;
        
        try {
            console.log('Sugiriendo categoría para:', titulo);
            
            const response = await fetch(`${this.baseUrl}/?action=ai_sugerir_categoria`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    titulo: titulo.trim(),
                    descripcion: descripcion.trim(),
                    csrf: this.getCSRF()
                })
            });

            console.log('Response status:', response.status);
            const data = await response.json();
            console.log('Response data:', data);
            
            if (!data.success) {
                throw new Error(data.error || data.details || 'Error al sugerir categoría');
            }

            // Intentar obtener categoría de múltiples fuentes
            let categoria = data.data?.categoria;
            let razon = data.data?.razon || 'Sugerencia automática';
            
            // Si no hay data pero hay texto, intentar extraer
            if (!categoria && data.text) {
                const text = data.text.toLowerCase();
                const categorias = ['soporte', 'desarrollo', 'reunion', 'bug'];
                for (const cat of categorias) {
                    if (text.includes(cat)) {
                        categoria = cat;
                        razon = 'Extraído del análisis';
                        break;
                    }
                }
            }
            
            const result = {
                categoria: categoria || 'desarrollo',
                razon: razon
            };
            
            // Guardar en caché
            window.AICache?.set('categoria', {titulo, descripcion}, result);
            
            return result;
        } catch (error) {
            console.error('Error AI sugerirCategoria:', error);
            throw error;
        } finally {
            this.isProcessing = false;
        }
    }

    /**
     * Analizar carga de trabajo actual
     */
    async analizarCarga(tareas) {
        this.isProcessing = true;
        
        try {
            const response = await fetch(`${this.baseUrl}/?action=ai_analizar_carga`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tareas: tareas,
                    csrf: this.getCSRF()
                })
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Error al analizar carga');
            }

            return {
                sugerencias: data.data?.sugerencias || [],
                carga: data.data?.carga || 'media',
                comentario: data.data?.comentario || ''
            };
        } catch (error) {
            console.error('Error AI analizarCarga:', error);
            throw error;
        } finally {
            this.isProcessing = false;
        }
    }

    /**
     * Mostrar indicador de carga
     */
    showLoading(element) {
        if (!element) return;
        
        const originalContent = element.innerHTML;
        element.setAttribute('data-original-content', originalContent);
        element.disabled = true;
        element.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Pensando...';
    }

    /**
     * Ocultar indicador de carga
     */
    hideLoading(element) {
        if (!element) return;
        
        const originalContent = element.getAttribute('data-original-content');
        if (originalContent) {
            element.innerHTML = originalContent;
        }
        element.disabled = false;
        element.removeAttribute('data-original-content');
    }

    /**
     * Verificar si está procesando
     */
    get processing() {
        return this.isProcessing;
    }
}

// Instancia global
window.aiAssistant = new AIAssistant();

// Exponer funciones auxiliares para UI
window.AI = {
    /**
     * Autocompletar descripción desde el modal de tarea
     */
    async autocompletarDescripcion() {
        const tituloInput = document.getElementById('cardTitle');
        const descripcionInput = document.getElementById('cardDesc');
        const btnIA = document.querySelector('[onclick*="autocompletarDescripcion"]');
        
        if (!tituloInput || !descripcionInput) {
            console.error('Inputs no encontrados');
            return;
        }

        const titulo = tituloInput.value.trim();
        
        if (!titulo) {
            alert('⚠️ Por favor escribe un título primero');
            tituloInput.focus();
            return;
        }

        if (titulo.length < 3) {
            alert('⚠️ El título debe tener al menos 3 caracteres');
            return;
        }

        try {
            window.aiAssistant.showLoading(btnIA);
            
            const descripcion = await window.aiAssistant.generarDescripcion(titulo);
            
            if (descripcion) {
                descripcionInput.value = descripcion;
                alert('✨ Descripción generada por IA');
                
                // Animación suave
                descripcionInput.style.backgroundColor = '#d1fae5';
                setTimeout(() => {
                    descripcionInput.style.backgroundColor = '';
                }, 1000);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Error al generar descripción: ' + error.message);
        } finally {
            window.aiAssistant.hideLoading(btnIA);
        }
    },

    /**
     * Estimar story points automáticamente
     */
    async estimarStoryPoints() {
        const tituloInput = document.getElementById('cardTitle');
        const descripcionInput = document.getElementById('cardDesc');
        const storyPointsInput = document.getElementById('cardPoints');
        const btnIA = document.querySelector('[onclick*="estimarStoryPoints"]');
        
        if (!tituloInput || !storyPointsInput) {
            console.error('Inputs no encontrados');
            return;
        }

        const titulo = tituloInput.value.trim();
        const descripcion = descripcionInput ? descripcionInput.value.trim() : '';
        
        if (!titulo) {
            alert('⚠️ Por favor escribe un título primero');
            tituloInput.focus();
            return;
        }

        try {
            window.aiAssistant.showLoading(btnIA);
            
            const resultado = await window.aiAssistant.estimarComplejidad(titulo, descripcion);
            
            if (resultado.storyPoints) {
                storyPointsInput.value = resultado.storyPoints;
                alert(`✨ Estimación: ${resultado.storyPoints} puntos\n${resultado.razon}`);
                
                // Animación suave
                storyPointsInput.style.backgroundColor = '#dbeafe';
                setTimeout(() => {
                    storyPointsInput.style.backgroundColor = '';
                }, 1000);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Error al estimar complejidad: ' + error.message);
        } finally {
            window.aiAssistant.hideLoading(btnIA);
        }
    },

    /**
     * Sugerir categoría automáticamente
     */
    async sugerirCategoria() {
        const tituloInput = document.getElementById('cardTitle');
        const descripcionInput = document.getElementById('cardDesc');
        const categoriaSelect = document.getElementById('cardCategoria');
        const btnIA = document.querySelector('[onclick*="sugerirCategoria"]');
        
        if (!tituloInput || !categoriaSelect) {
            console.error('Inputs no encontrados');
            return;
        }

        const titulo = tituloInput.value.trim();
        const descripcion = descripcionInput ? descripcionInput.value.trim() : '';
        
        if (!titulo) {
            alert('⚠️ Por favor escribe un título primero');
            tituloInput.focus();
            return;
        }

        try {
            window.aiAssistant.showLoading(btnIA);
            
            const resultado = await window.aiAssistant.sugerirCategoria(titulo, descripcion);
            
            if (resultado.categoria) {
                categoriaSelect.value = resultado.categoria;
                alert(`✨ Categoría sugerida: ${resultado.categoria}\n${resultado.razon}`);
                
                // Animación suave
                categoriaSelect.style.backgroundColor = '#dcfce7';
                setTimeout(() => {
                    categoriaSelect.style.backgroundColor = '';
                }, 1000);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Error al sugerir categoría: ' + error.message);
        } finally {
            window.aiAssistant.hideLoading(btnIA);
        }
    },

    /**
     * Generar checklist de subtareas
     */
    async generarChecklist() {
        const tituloInput = document.getElementById('cardTitle');
        const descripcionInput = document.getElementById('cardDesc');
        const checklistContainer = document.querySelector('.checklist-items');
        const btnIA = document.querySelector('[onclick*="generarChecklist"]');
        
        if (!tituloInput) {
            console.error('Input de título no encontrado');
            return;
        }

        const titulo = tituloInput.value.trim();
        const descripcion = descripcionInput ? descripcionInput.value.trim() : '';
        
        if (!titulo) {
            alert('⚠️ Por favor escribe un título primero');
            tituloInput.focus();
            return;
        }

        try {
            window.aiAssistant.showLoading(btnIA);
            
            const subtareas = await window.aiAssistant.generarSubtareas(titulo, descripcion);
            
            if (subtareas && subtareas.length > 0) {
                // Si existe un checklist container, agregar ahí
                if (checklistContainer) {
                    subtareas.forEach(subtarea => {
                        // Usar la función existente de agregar checklist item si existe
                        if (typeof app.addChecklistItem === 'function') {
                            app.addChecklistItem(subtarea);
                        }
                    });
                }
                
                alert(`✨ ${subtareas.length} subtareas generadas por IA`);
            } else {
                alert('⚠️ No se pudieron generar subtareas');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Error al generar subtareas: ' + error.message);
        } finally {
            window.aiAssistant.hideLoading(btnIA);
        }
    }
};

console.log('✨ AI Assistant Module cargado - Gemini API integrada');
