pipeline {
    agent any

    environment {
        BECOME_PASSWORD = credentials('ansible-become-password')
    }

    stages {

        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Test syntaxe Ansible') {
            steps {
                bat 'wsl ansible-playbook --syntax-check -i inventory/hosts.ini playbook.yml'
            }
        }

        stage('Lint Ansible') {
            steps {
                bat 'wsl ansible-lint playbook.yml || true'
            }
        }

        stage('Validation manuelle') {
            steps {
                input message: 'Tests OK. Déployer sur 192.168.8.2 ?', ok: 'Déployer'
            }
        }

        stage('Déploiement') {
            steps {
                bat """
                    wsl bash -c \"ANSIBLE_BECOME_PASS=${BECOME_PASSWORD} ansible-playbook -i inventory/hosts.ini playbook.yml\"
                """
            }
        }
    }

    post {
        success { echo 'Déploiement réussi sur le serveur Debian !' }
        failure { echo 'Echec. Consulte les logs Jenkins.' }
    }
}