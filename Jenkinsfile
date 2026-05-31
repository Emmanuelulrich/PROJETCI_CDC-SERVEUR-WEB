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
                bat 'wsl ansible-playbook --syntax-check -i inventory/hosts.ini asrc_config.yml'
            }
        }

        stage('Lint Ansible') {
            steps {
                bat 'wsl bash -c "ansible-lint asrc_config.yml || true"'
            }
        }

        stage('Déploiement') {
            steps {
                bat """
                    wsl bash -c \"ANSIBLE_BECOME_PASS=${BECOME_PASSWORD} ansible-playbook -i inventory/hosts.ini asrc_config.yml\"
                """
            }
        }
    }

    post {
        success { echo 'Déploiement réussi !' }
        failure { echo 'Echec. Consulte les logs.' }
    }
}