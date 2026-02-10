# Vehicle Tracker - Plugin WordPress para Rastreamento Veicular

Plugin profissional para rastreamento de veículos em tempo real com integração API Avatek e suporte a rastreadores Suntech ST8310UM.

## Recursos

- **Rastreamento em tempo real** via Google Maps
- **Integração com API Avatek** para comunicação com rastreadores
- **Servidor TCP/UDP** para receber dados diretamente dos dispositivos
- **Parser de protocolo Suntech** para decodificar mensagens
- **Dashboard administrativo** completo
- **Geocercas** (círculo, retângulo, polígono)
- **Sistema de alertas** configuráveis
- **Relatórios** de viagens, velocidade, paradas
- **Shortcodes** para exibição pública
- **API REST** para integração com outros sistemas

## Requisitos

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+
- Chave de API do Google Maps
- Credenciais da API Avatek (opcional)
- Servidor com suporte a sockets para TCP/UDP (opcional)

## Instalação

1. Faça upload da pasta `vehicle-tracker` para `/wp-content/plugins/`
2. Ative o plugin no menu Plugins do WordPress
3. Acesse **Rastreamento > Configurações** e configure:
   - Chave da API do Google Maps
   - Credenciais da API Avatek (se usar)
   - Configurações do servidor TCP/UDP (se usar)

## Configuração da API Avatek

1. Obtenha suas credenciais em https://avatek.docs.apiary.io/
2. Em **Configurações > API Avatek**, insira:
   - URL da API
   - API Key
   - ID da Conta (Account ID)
3. Clique em "Testar Conexão" para verificar
4. Use "Sincronizar Veículos" para importar os dispositivos

## Configuração do Servidor TCP

Para receber dados diretamente dos rastreadores Suntech:

1. Configure seu servidor para permitir conexões TCP na porta desejada
2. Em **Configurações > Servidor TCP**, insira:
   - Porta TCP (ex: 9000)
   - IP do Servidor (seu IP público)
3. Configure os rastreadores para enviar dados para seu servidor:
   - IP: seu IP público
   - Porta: a configurada acima
   - Protocolo: Suntech ST8310UM

### Comandos AT para Suntech ST8310UM

Configure o rastreador via SMS ou SyncTrak:

```
ST300CMD;[IMEI];02;SetServerIP;[SEU_IP];[PORTA]
ST300CMD;[IMEI];02;SetGPRS;[APN_TIM];[USUARIO];[SENHA]
```

Para chip TIM:
- APN: `timbrasil.br`
- Usuário: `tim`
- Senha: `tim`

## Shortcodes

### [vehicle_tracker]
Mapa de rastreamento completo com lista de veículos.

```
[vehicle_tracker user_vehicles="true" show_history="true" height="600px"]
```

### [vehicle_tracker_map]
Mapa simples de um veículo específico.

```
[vehicle_tracker_map vehicle_id="123" height="400px" show_info="true"]
```

### [vehicle_tracker_fleet]
Grade de cartões da frota.

```
[vehicle_tracker_fleet columns="4" show_status="true" show_location="true"]
```

## API REST

O plugin expõe endpoints REST para integração:

- `GET /wp-json/vehicle-tracker/v1/vehicles` - Lista veículos
- `GET /wp-json/vehicle-tracker/v1/vehicles/{id}` - Detalhes do veículo
- `GET /wp-json/vehicle-tracker/v1/vehicles/{id}/history` - Histórico de posições
- `POST /wp-json/vehicle-tracker/v1/vehicles/{id}/command` - Enviar comando

Autenticação via header: `X-API-Key: sua_chave_api`

## Estrutura de Arquivos

```
vehicle-tracker/
├── vehicle-tracker.php          # Arquivo principal
├── includes/
│   ├── class-vt-database.php    # Gerenciamento do banco
│   ├── class-vt-avatek-api.php  # Integração Avatek
│   ├── class-vt-suntech-parser.php # Parser protocolo
│   ├── class-vt-tcp-server.php  # Servidor TCP
│   ├── class-vt-vehicle.php     # CRUD veículos
│   ├── class-vt-geofence.php    # CRUD geocercas
│   ├── class-vt-alert.php       # Sistema de alertas
│   └── class-vt-rest-api.php    # Endpoints REST
├── admin/
│   ├── class-vt-admin.php       # Admin principal
│   ├── class-vt-admin-dashboard.php
│   ├── class-vt-admin-vehicles.php
│   ├── class-vt-admin-tracking.php
│   ├── class-vt-admin-history.php
│   ├── class-vt-admin-geofences.php
│   ├── class-vt-admin-reports.php
│   └── class-vt-admin-settings.php
├── public/
│   └── class-vt-public.php      # Funcionalidades públicas
└── assets/
    ├── css/
    │   ├── admin-style.css
    │   └── public-style.css
    └── js/
        ├── admin-script.js
        ├── tracking-map.js
        └── public-script.js
```

## Tabelas do Banco de Dados

O plugin cria as seguintes tabelas:

- `{prefix}_vt_vehicles` - Veículos cadastrados
- `{prefix}_vt_positions` - Histórico de posições
- `{prefix}_vt_geofences` - Geocercas
- `{prefix}_vt_alerts` - Alertas gerados
- `{prefix}_vt_drivers` - Motoristas
- `{prefix}_vt_commands` - Comandos enviados

## Protocolo Suntech ST8310UM

O parser suporta as seguintes mensagens:

- **STT** - Status/Posição
- **EMG** - Emergência
- **EVT** - Evento
- **ALT** - Alerta
- **CMD** - Resposta de comando

Campos decodificados:
- IMEI, Data/Hora, Latitude, Longitude
- Velocidade, Direção, Satélites
- Status da ignição, Odômetro
- Nível de bateria, Sinal GSM

## Suporte

Para dúvidas ou problemas, abra uma issue no repositório ou entre em contato com o desenvolvedor.

## Licença

GPL v2 ou posterior
