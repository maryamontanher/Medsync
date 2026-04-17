# MedSync - Sistema de Prontuário Eletrônico

O **MedSync** é um sistema de prontuário eletrônico desenvolvido para facilitar o agendamento de consultas envio de receitas e emissão de atestados.

A plataforma integra diferentes tipos de usuários em um único ambiente (Administração, Médicos, Farmácia, paciente maior e menor de idade), facilitando a comunicação e o acesso às informações clínicas.

---

##  Sobre o Projeto

O MedSync foi projetado para simular um sistema real da área da saúde, permitindo o gerenciamento completo de pacientes, médicos e farmácia.

O objetivo é oferecer uma solução digital que centralize dados médicos, reduzindo a burocracia e melhorando a experiência tanto dos profissionais quanto dos pacientes.

---

## Tipos de Usuários

O sistema possui múltiplos níveis de acesso:
### Administração
- Cadastro de farmácias  
- Cadastro de médicos
- Cadastro de pacientes Maiores e menores de idade

### Médicos
- Visualização de consultas agendadas  
- Acesso ao histórico do paciente  
- Emissão de receitas médicas  
- Emissão de atestados 
- Emissão de anamnese
- Acesso ao perfil  
- Vizualização de relatórios 

### Pacientes (Maiores e Menores)
- Acesso ao perfil  
- Agendamento de consultas
- Vizualização de atestados e receitas  
- Para menores: vínculo com responsável  

### Farmácias
- Acesso às receitas emitidas
- Acesso ao estoque de medicamentos 
- Validação de prescrições  

---

## Funcionalidades

-  Cadastro de usuários (médicos, pacientes e farmácias)  
-  Sistema de login com validação entre múltiplas tabelas  
-  Prontuário eletrônico digital  
-  Emissão de receitas e atestados  
-  Controle de consultas  
-  Validação de dados únicos (CPF, email, telefone, CRM)  
-  Integração entre diferentes tipos de usuários  
-  Envio de senha temporária por e-mail  

---

## Tecnologias Utilizadas

| Categoria         | Tecnologias               |
|-------------------|---------------------------|
|  Frontend         | HTML, CSS, JavaScript     |
|  Backend          | PHP                       |
|  Banco de Dados   | MySQL                     |
|  Integração       | PHPMailer                 |

---

## Estrutura do Banco de Dados

O sistema utiliza múltiplas tabelas relacionadas:

- `medicos`
- `pacientes_maiores`
- `pacientes_menores`
- `farmacia`

Relacionamentos:
- Pacientes menores possuem vínculo com responsáveis  
- Validação de dados únicos entre todas as tabelas  

---

## Segurança e Validação

- Validação de dados obrigatórios  
- Verificação de duplicidade (CPF, email, telefone)  
- Controle de acesso por tipo de usuário  
- Envio seguro de credenciais  

---


## Autor

Desenvolvido por **Marya Eduarda, Amanda e Maria Clara** 
Projeto voltado para aprendizado e desenvolvimento na área de sistemas web.
