<!-- Vista Kanban -->
<div id="board" class="kanban"></div>
  
<!-- Vista Lista -->
<div id="vistaLista" style="display:none;">
  <!-- Filtros -->
  <div class="mb-3 p-3" style="background: rgba(30, 41, 59, 0.5); border-radius: 12px; border: 1px solid rgba(148, 163, 184, 0.2);">
    <div class="row g-3 align-items-end justify-content-center">
      <!-- Filtro de Rango de Fechas -->
      <div class="col-md-2">
        <label class="form-label text-light fw-semibold small">📅 Fecha Inicio</label>
        <input type="date" id="filtroFechaInicio" class="form-control form-control-sm bg-dark text-light border-secondary" style="border-radius: 8px;">
      </div>
      <div class="col-md-2">
        <label class="form-label text-light fw-semibold small">📅 Fecha Fin</label>
        <input type="date" id="filtroFechaFin" class="form-control form-control-sm bg-dark text-light border-secondary" style="border-radius: 8px;">
      </div>
      
      <!-- Filtro de Estado -->
      <div class="col-md-2">
        <label class="form-label text-light fw-semibold small">🎯 Estado</label>
        <select id="filtroEstado" class="form-select form-select-sm bg-dark text-light border-secondary" style="border-radius: 8px;">
          <option value="">Todos</option>
          <option value="pendiente">⏳ Pendiente</option>
          <option value="en_progreso">🔄 En Progreso</option>
          <option value="hecho">✅ Hecho</option>
        </select>
      </div>
      
      <!-- Filtro de Categoría -->
      <div class="col-md-2">
        <label class="form-label text-light fw-semibold small">🏷️ Categoría</label>
        <select id="filtroCategoria" class="form-select form-select-sm bg-dark text-light border-secondary" style="border-radius: 8px;">
          <option value="">Todas</option>
          <option value="soporte">🛠️ Soporte</option>
          <option value="desarrollo">💻 Desarrollo</option>
          <option value="reunion">👥 Reunión</option>
          <option value="bug">🐛 Bug</option>
        </select>
      </div>
      
      <!-- Botones de Selección Rápida -->
      <div class="col-md-4">
        <label class="form-label text-light fw-semibold small">⚡ Selección Rápida</label>
        <div class="d-flex gap-2">
          <button id="btnFiltroAyer" class="btn btn-sm btn-warning" style="border-radius: 8px; flex: 1;">⏮️ Ayer</button>
          <button id="btnFiltroHoy" class="btn btn-sm btn-success" style="border-radius: 8px; flex: 1;">📍 Hoy</button>
          <button id="btnFiltroSemanaActual" class="btn btn-sm btn-info" style="border-radius: 8px; flex: 1;">📆 Semana</button>
          <button id="btnFiltroMesActual" class="btn btn-sm btn-primary" style="border-radius: 8px; flex: 1;">📅 Mes</button>
          <button id="btnLimpiarFiltros" class="btn btn-sm btn-secondary" style="border-radius: 8px;">🔄</button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Tabla -->
  <div class="table-responsive">
    <table class="table table-dark table-striped table-hover">
      <thead>
        <tr>
          <th style="width: 60px;">#ID</th>
          <th>Título</th>
          <th style="width: 150px;">Estado</th>
          <th style="width: 130px;">Categoría</th>
          <th style="width: 80px;">Puntos</th>
          <th style="width: 150px;">Fecha Entrega</th>
          <th style="width: 120px;">Tipo</th>
          <th style="width: 100px;">Acciones</th>
        </tr>
      </thead>
      <tbody id="listaTableBody">
        <tr>
          <td colspan="8" class="text-center text-secondary">Cargando...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Vista Informe de Productividad -->
<div id="vistaInforme" style="display:none;">
  <div class="row g-4">
    
    <!-- Tarjetas de Resumen -->
    <div class="col-12">
      <div class="row g-3">
        <!-- Total Tareas -->
        <div class="col-md-3">
          <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 16px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);">
            <div class="card-body text-center py-4">
              <div class="display-4 fw-bold text-white mb-2" id="statTotalTareas">0</div>
              <div class="text-white-50 fw-semibold">📋 Total Tareas</div>
            </div>
          </div>
        </div>
        
        <!-- Tareas Completadas -->
        <div class="col-md-3">
          <div class="card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; border-radius: 16px; box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);">
            <div class="card-body text-center py-4">
              <div class="display-4 fw-bold text-white mb-2" id="statCompletadas">0</div>
              <div class="text-white-50 fw-semibold">✅ Completadas</div>
            </div>
          </div>
        </div>
        
        <!-- En Progreso -->
        <div class="col-md-3">
          <div class="card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none; border-radius: 16px; box-shadow: 0 8px 24px rgba(245, 158, 11, 0.4);">
            <div class="card-body text-center py-4">
              <div class="display-4 fw-bold text-white mb-2" id="statEnProgreso">0</div>
              <div class="text-white-50 fw-semibold">🔄 En Progreso</div>
            </div>
          </div>
        </div>
        
        <!-- Tasa de Completitud -->
        <div class="col-md-3">
          <div class="card" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none; border-radius: 16px; box-shadow: 0 8px 24px rgba(59, 130, 246, 0.4);">
            <div class="card-body text-center py-4">
              <div class="display-4 fw-bold text-white mb-2" id="statTasaCompletitud">0%</div>
              <div class="text-white-50 fw-semibold">⚡ Tasa Completitud</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Gráfico de Progreso -->
    <div class="col-md-8">
      <div class="card bg-dark border-0" style="border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
        <div class="card-body p-4">
          <h5 class="card-title text-light mb-4 fw-bold">📊 Progreso del Sprint</h5>
          
          <!-- Barra de Progreso General -->
          <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-light fw-semibold">Progreso General</span>
              <span class="text-light fw-bold" id="progresoGeneral">0%</span>
            </div>
            <div class="progress" style="height: 25px; border-radius: 12px; background: rgba(148, 163, 184, 0.2);">
              <div id="barraProgresoGeneral" class="progress-bar" role="progressbar" style="width: 0%; background: linear-gradient(90deg, #667eea, #764ba2); font-weight: 700; font-size: 14px;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                0%
              </div>
            </div>
          </div>
          
          <!-- Distribución por Estado -->
          <div class="row g-3 mt-3">
            <div class="col-4">
              <div class="p-3 text-center" style="background: rgba(148, 163, 184, 0.1); border-radius: 12px; border-left: 4px solid #6b7280;">
                <div class="h3 mb-1 text-light fw-bold" id="statPendientes">0</div>
                <div class="small text-secondary">⏳ Pendientes</div>
              </div>
            </div>
            <div class="col-4">
              <div class="p-3 text-center" style="background: rgba(245, 158, 11, 0.1); border-radius: 12px; border-left: 4px solid #f59e0b;">
                <div class="h3 mb-1 text-light fw-bold" id="statEnProgresoDetalle">0</div>
                <div class="small text-secondary">🔄 En Curso</div>
              </div>
            </div>
            <div class="col-4">
              <div class="p-3 text-center" style="background: rgba(16, 185, 129, 0.1); border-radius: 12px; border-left: 4px solid #10b981;">
                <div class="h3 mb-1 text-light fw-bold" id="statCompletadasDetalle">0</div>
                <div class="small text-secondary">✅ Hechas</div>
              </div>
            </div>
          </div>
          
          <!-- Story Points -->
          <div class="mt-4 p-3" style="background: rgba(59, 130, 246, 0.1); border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.3);">
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-light fw-semibold">📈 Story Points</span>
              <span class="text-light fw-bold"><span id="puntosCompletadosInforme">0</span> / <span id="puntosTotalesInforme">0</span></span>
            </div>
            <div class="progress mt-2" style="height: 20px; border-radius: 10px; background: rgba(148, 163, 184, 0.2);">
              <div id="barraPuntos" class="progress-bar bg-info" role="progressbar" style="width: 0%; font-weight: 600;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                0%
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Insights y Sugerencias -->
    <div class="col-md-4">
      <div class="card bg-dark border-0" style="border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); height: 100%;">
        <div class="card-body p-4">
          <h5 class="card-title text-light mb-4 fw-bold">💡 Insights IA</h5>
          <div id="insightsContainer">
            <!-- Los insights se generarán dinámicamente -->
          </div>
        </div>
      </div>
    </div>
    
    <!-- Categorías más utilizadas -->
    <div class="col-md-6">
      <div class="card bg-dark border-0" style="border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
        <div class="card-body p-4">
          <h5 class="card-title text-light mb-4 fw-bold">🏷️ Categorías</h5>
          <div id="categoriasContainer">
            <!-- Las categorías se generarán dinámicamente -->
          </div>
        </div>
      </div>
    </div>
    
    <!-- Productividad Diaria -->
    <div class="col-md-6">
      <div class="card bg-dark border-0" style="border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
        <div class="card-body p-4">
          <h5 class="card-title text-light mb-4 fw-bold">📅 Productividad Últimos 7 Días</h5>
          <div id="productividadDiariaContainer">
            <!-- La productividad diaria se generará dinámicamente -->
          </div>
        </div>
      </div>
    </div>
    
    <!-- Plan de Acción Recomendado por IA -->
    <div class="col-12">
      <div class="card border-0" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(37, 99, 235, 0.1) 100%); border-radius: 16px; border: 2px solid rgba(59, 130, 246, 0.4); box-shadow: 0 8px 32px rgba(59, 130, 246, 0.2);">
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="card-title text-light mb-0 fw-bold">
              <span class="badge" style="background: linear-gradient(135deg, #3b82f6, #2563eb); font-size: 1rem; padding: 0.5rem 1rem; border-radius: 8px;">
                🤖 Plan de Acción Recomendado por IA
              </span>
            </h5>
            <span class="badge bg-success">Sistema Inteligente Activo</span>
          </div>
          
          <div class="alert alert-info mb-4" style="background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 12px;">
            <div class="d-flex align-items-center gap-2">
              <span style="font-size: 1.5rem;">🧠</span>
              <div>
                <strong class="text-light">Algoritmo Multi-Factor Activado</strong>
                <p class="mb-0 small text-secondary mt-1">
                  Analizando: Urgencia, Complejidad, Dependencias, Esfuerzo, Patrones históricos y Momentum actual
                </p>
              </div>
            </div>
          </div>
          
          <div id="planAccionContainer">
            <!-- El plan de acción se generará dinámicamente -->
          </div>
        </div>
      </div>
    </div>
    
    <!-- Tareas Atrasadas -->
    <div class="col-12" id="tareasAtrasadasSection" style="display: none;">
      <div class="card border-0" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(220, 38, 38, 0.1) 100%); border-radius: 16px; border: 2px solid rgba(239, 68, 68, 0.3);">
        <div class="card-body p-4">
          <h5 class="card-title text-danger mb-4 fw-bold">🚨 Tareas Atrasadas</h5>
          <div id="tareasAtrasadasContainer" class="row g-3">
            <!-- Las tareas atrasadas se generarán dinámicamente -->
          </div>
        </div>
      </div>
    </div>
    
    <!-- Recomendaciones Personalizadas -->
    <div class="col-12">
      <div class="card border-0" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(124, 58, 237, 0.1) 100%); border-radius: 16px; border: 2px solid rgba(139, 92, 246, 0.3);">
        <div class="card-body p-4">
          <h5 class="card-title text-light mb-4 fw-bold">🎯 Recomendaciones para Maximizar tu Productividad</h5>
          <div id="recomendacionesContainer" class="row g-3">
            <!-- Las recomendaciones se generarán dinámicamente -->
          </div>
        </div>
      </div>
    </div>
    
  </div>
</div>
