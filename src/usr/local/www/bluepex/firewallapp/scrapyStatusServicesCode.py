from multiprocessing import Pool
import telnetlib
import os
import json


def validade_telnet(alvo):
    try:
        with open("{}{}".format(diretorio_tmp, arquivo_tmp), "a+") as return_file:
            conectou = False
            try:
                telnet = telnetlib.Telnet()
                telnet.open(alvo, '443', 2)
                conectou = True
            except:
                try:
                    telnet = telnetlib.Telnet()
                    telnet.open(alvo, '80', 2)
                    conectou = True
                except:
                    pass

            return_file.write("\"{}\":\"{}\",".format(alvo, conectou))
    except:
        pass

if __name__ == "__main__":


    #Preparar um diretorio
    global diretorio_tmp
    global arquivo_tmp
    global arquivo_final

    diretorio_tmp = '/tmp/categorias/'
    arquivo_tmp = 'status_services_gerais.status.services.tmp'
    arquivo_final = 'status_services_gerais.status.services'

    try:
        os.makedirs(diretorio_tmp)    
    except:
        pass

    try:
        os.remove("{}{}".format(diretorio_tmp, arquivo_tmp))
    except:
        pass


    uniqueUrls = [
        'www.whatsapp.com',
        'www.tiktok.com',
        'www.instagram.com',
        'www.facebook.com',
        'www.telegram.com',
        'www.twitter.com',
        'www.linkedin.com',
        'news.microsoft.com',
        'www.uol.com',
        'www.google.com',
        'www.g1.com',
        'www.amazon.com',
        'www.yahoo.com',
        'www.primevideo.com',
        'www.netflix.com',
        'www.youtube.com',
        'www.disneyplus.com',
        'www.twitch.tv',
        'www.deezer.com',
        'music.amazon.com',
        'www.spotify.com',
        'www.teamviewer.com',
        'www.anydesk.com',
        'www.teste.com',
        'www.amazon.com'
    ]
    
    with open("{}{}".format(diretorio_tmp, arquivo_tmp), "a+") as return_file:
        return_file.write("{")


    #Iniciar um processo
    pool = Pool(processes=10)
    saidaTotal = pool.map(validade_telnet, uniqueUrls)
    pool.close()

    with open("{}{}".format(diretorio_tmp, arquivo_tmp), "a+") as return_file:
        return_file.write("\"0\":\"0\"")
    with open("{}{}".format(diretorio_tmp, arquivo_tmp), "a+") as return_file:
        return_file.write("}")

    os.system("cp {}{} {}{}".format(diretorio_tmp, arquivo_tmp, diretorio_tmp, arquivo_final))

    os.system("pkill -af scrapyStatusServicesCode.py")