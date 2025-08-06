from multiprocessing import Pool
from time import sleep, time
import telnetlib
import subprocess
import requests
import os


#gerar os arquivos temp 
def validade_status(trabalhosDoMomento):
    try:
        #for url_target in uniqueUrls:
        work_now = trabalhosDoMomento.split('-explodeAqui-')
        file_target = work_now[0]
        url_target = work_now[1]
        with open("{}{}.status.tmp".format(diretorio_tmp, file_target), "a+") as return_file:
            tempoOperacao = 2
            status_code_now = "||1"
            try:
                target_site = requests.get("https://{}".format(url_target), timeout=tempoOperacao)
                status_code_now = "||{}".format(target_site.status_code)
            except:
                pass
            if status_code_now == "||1":
                try:
                    target_site = requests.get("http://{}".format(url_target), timeout=tempoOperacao)
                    status_code_now = "||{}".format(target_site.status_code)
                except:
                    pass
            ping_now = "||1"
            try:
                subprocess.check_output(["ping", "-t", "{}".format(tempoOperacao), "-c", "1", url_target])
                ping_now = '||0'
            except:
                pass
            telnet_now = '||1'
            try:
                telnet = telnetlib.Telnet()
                telnet.open(url_target, '443', tempoOperacao)
                telnet_now = '||0'
            except:
                try:
                    telnet = telnetlib.Telnet()
                    telnet.open(url_target, '80', tempoOperacao)
                    telnet_now = '||0'
                except:
                    pass

            #print("{}{}{}".format(url_target, status_code_now, ping_now))
            return_file.write("{}{}{}{}\n".format(url_target, status_code_now, ping_now, telnet_now))
    except:
        pass


#Start process
if __name__ == "__main__":

    #start = time()

    #Preparar um diretorio
    global diretorio_tmp
    diretorio_tmp = '/tmp/categorias/'
    try:
        os.makedirs(diretorio_tmp)    
    except:
        pass


    #Pegar todas as urls, limpar e preparar um array para se trabalhar
    os.system("grep -r ':' /usr/local/share/suricata/rules_fapp/*.rules | awk -F'/usr/local/share/suricata/rules_fapp/' '{ print $2 }' > /tmp/categorias/regras_a_trabalhar")
    with open('/tmp/categorias/regras_a_trabalhar') as f:
        Alllines = f.readlines()

    allUrls = []
    ArquivosCategorias = []
    for line in Alllines:
        if line[0] != "_":
            if line.find("reference:url,") != -1:
                lineNow = line.split('\n')[0]
                categoria = lineNow.split(":")[0]
                ArquivosCategorias.append(categoria)
                url = lineNow.split("url,")[1].split(";")[0]
                allUrls.append("{}-explodeAqui-{}".format(categoria, url))

    """
    for line in Alllines:
        if line.find("content:\"") != -1:
            lineNow = line.split('\n')[0]
            categoria = lineNow.split(":")[0]
            ArquivosCategorias.append(categoria)
            url = lineNow.split("content:\"")[1].split("\";")[0]
            if url.find(".") != -1:
                allUrls.append("{}-explodeAqui-{}".format(categoria, url))

    for line in Alllines:
        if line.find("content:'") != -1:
            lineNow = line.split('\n')[0]
            categoria = lineNow.split(":")[0]
            ArquivosCategorias.append(categoria)
            url = lineNow.split("content:'")[1].split("';")[0]
            if url.find(".") != -1:
                allUrls.append("{}-explodeAqui-{}".format(categoria, url))
    """

    #Limpeza
    del Alllines
    uniqueUrls = []
    for line in allUrls:
        try:
            uniqueUrls.index(line)
        except:
            uniqueUrls.append(line)
    del allUrls

    ArquivosCategoriasUnicos = []
    for line in ArquivosCategorias:
        try:
            ArquivosCategoriasUnicos.index(line)
        except:
            ArquivosCategoriasUnicos.append(line)
    del ArquivosCategorias

    """
    ArquivosCategoriasUnicos - Todas as categorias de regras
    uniqueUrls - Todos os valores unicos
    """

    #Deletar todos os temporarios para atualizar
    os.system('rm -rf {}*.status.tmp'.format(diretorio_tmp))
        
    #Iniciar um processo
    pool = Pool(processes=10)
    pool.map(validade_status, uniqueUrls)
    pool.close()


    #Aqui converte arquivo temp para status em uso
    os.system('rm -rf {}*.status'.format(diretorio_tmp))
    for file_now in ArquivosCategoriasUnicos:
        os.system('cp {}{}.status.tmp {}{}.status'.format(diretorio_tmp,file_now,diretorio_tmp,file_now))
    
    #Saida tempo
    #end = time()
    #print('tempo de exec da: {}'.format(end - start))

    os.system("pkill -af scrapyStatusServicesSites.py")